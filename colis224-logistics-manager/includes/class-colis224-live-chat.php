<?php
/**
 * MODULE 22: Live Chat Support
 * Chat en temps réel entre clients et support
 * Disponible uniquement dans l'espace client connecté
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Live_Chat {

    public function __construct() {
        // Hooks AJAX pour le chat
        add_action('wp_ajax_colis224_send_chat_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_nopriv_colis224_send_chat_message', array($this, 'ajax_send_message'));

        add_action('wp_ajax_colis224_get_chat_messages', array($this, 'ajax_get_messages'));
        add_action('wp_ajax_nopriv_colis224_get_chat_messages', array($this, 'ajax_get_messages'));

        add_action('wp_ajax_colis224_get_chat_conversations', array($this, 'ajax_get_conversations'));
        add_action('wp_ajax_colis224_close_chat', array($this, 'ajax_close_chat'));
        add_action('wp_ajax_colis224_agent_status', array($this, 'ajax_update_agent_status'));

        // Shortcode pour le widget chat
        add_shortcode('colis224_live_chat', array($this, 'chat_widget_shortcode'));
    }

    /**
     * Créer les tables pour le live chat
     */
    public static function create_chat_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des conversations
        $table_conversations = $wpdb->prefix . 'colis224_chat_conversations';
        $sql_conversations = "CREATE TABLE $table_conversations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id varchar(50) NOT NULL,
            client_id bigint(20) UNSIGNED DEFAULT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) DEFAULT NULL,
            client_phone varchar(50) DEFAULT NULL,
            assigned_agent_id bigint(20) UNSIGNED DEFAULT NULL,
            status enum('active','waiting','closed') DEFAULT 'waiting',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_message_at datetime DEFAULT CURRENT_TIMESTAMP,
            closed_at datetime DEFAULT NULL,
            rating tinyint(1) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY conversation_id (conversation_id),
            KEY client_id (client_id),
            KEY assigned_agent_id (assigned_agent_id),
            KEY status (status),
            KEY last_message_at (last_message_at)
        ) $charset_collate;";
        dbDelta($sql_conversations);

        // Table des messages
        $table_messages = $wpdb->prefix . 'colis224_chat_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id varchar(50) NOT NULL,
            sender_type enum('client','agent','system') DEFAULT 'client',
            sender_id bigint(20) UNSIGNED DEFAULT NULL,
            sender_name varchar(255) NOT NULL,
            message text NOT NULL,
            attachment varchar(255) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id),
            KEY sender_type (sender_type),
            KEY created_at (created_at),
            KEY is_read (is_read)
        ) $charset_collate;";
        dbDelta($sql_messages);

        // Table des agents en ligne
        $table_agents_status = $wpdb->prefix . 'colis224_chat_agents_status';
        $sql_agents_status = "CREATE TABLE $table_agents_status (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) UNSIGNED NOT NULL,
            agent_name varchar(255) NOT NULL,
            agent_email varchar(255) DEFAULT NULL,
            status enum('online','offline','busy','away') DEFAULT 'offline',
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            active_conversations int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY agent_id (agent_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_agents_status);

        // Table des réponses rapides
        $table_quick_replies = $wpdb->prefix . 'colis224_chat_quick_replies';
        $sql_quick_replies = "CREATE TABLE $table_quick_replies (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            category varchar(100) DEFAULT 'general',
            is_active tinyint(1) DEFAULT 1,
            usage_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY category (category),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_quick_replies);

        // Créer des réponses rapides par défaut
        self::create_default_quick_replies();
    }

    /**
     * Créer des réponses rapides par défaut
     */
    private static function create_default_quick_replies() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_quick_replies';

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $replies = array(
            array(
                'title' => 'Bienvenue',
                'message' => 'Bonjour! Bienvenue sur Colis224. Comment puis-je vous aider aujourd\'hui?',
                'category' => 'greeting'
            ),
            array(
                'title' => 'Suivi de colis',
                'message' => 'Pour suivre votre colis, merci de me communiquer votre numéro de tracking.',
                'category' => 'tracking'
            ),
            array(
                'title' => 'Paiement',
                'message' => 'Concernant le paiement, nous acceptons les modes suivants: Mobile Money (Orange, MTN, Moov), PayPal, et espèces à l\'agence.',
                'category' => 'payment'
            ),
            array(
                'title' => 'Délais de livraison',
                'message' => 'Les délais de livraison varient selon la destination. En général: Chine 15-20 jours, France 10-15 jours, Afrique 7-10 jours.',
                'category' => 'delivery'
            ),
            array(
                'title' => 'Horaires',
                'message' => 'Nos agences sont ouvertes du Lundi au Samedi de 8h à 18h. Dimanche de 9h à 13h.',
                'category' => 'general'
            ),
            array(
                'title' => 'Merci',
                'message' => 'Merci de nous avoir contactés. N\'hésitez pas si vous avez d\'autres questions!',
                'category' => 'closing'
            )
        );

        foreach ($replies as $reply) {
            $wpdb->insert($table, $reply);
        }
    }

    /**
     * Démarrer ou récupérer une conversation
     */
    public static function get_or_create_conversation($client_id = null, $client_name = '', $client_email = '', $client_phone = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_conversations';

        // Si client connecté, chercher une conversation active
        if ($client_id) {
            $conversation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE client_id = %d AND status IN ('active', 'waiting') ORDER BY last_message_at DESC LIMIT 1",
                $client_id
            ));

            if ($conversation) {
                return $conversation;
            }
        }

        // Créer une nouvelle conversation
        $conversation_id = 'CHAT-' . strtoupper(wp_generate_password(12, false));

        $wpdb->insert($table, array(
            'conversation_id' => $conversation_id,
            'client_id' => $client_id,
            'client_name' => $client_name,
            'client_email' => $client_email,
            'client_phone' => $client_phone,
            'status' => 'waiting'
        ));

        // Envoyer notification aux agents disponibles
        self::notify_available_agents($conversation_id, $client_name);

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE conversation_id = %s",
            $conversation_id
        ));
    }

    /**
     * Notifier les agents disponibles
     */
    private static function notify_available_agents($conversation_id, $client_name) {
        global $wpdb;
        $table_agents = $wpdb->prefix . 'colis224_chat_agents_status';

        // Récupérer les agents en ligne
        $agents = $wpdb->get_results(
            "SELECT * FROM $table_agents WHERE status IN ('online', 'away') ORDER BY active_conversations ASC LIMIT 5"
        );

        if (empty($agents)) {
            // Si aucun agent en ligne, notifier tous les admins
            $admin_email = get_option('admin_email');
            $subject = "Nouveau chat client - Colis224";
            $message = "Le client $client_name a démarré une conversation et attend une réponse.\n\n";
            $message .= "Connectez-vous pour répondre: " . admin_url('admin.php?page=colis224-live-chat');

            wp_mail($admin_email, $subject, $message);
        } else {
            // Notifier le premier agent disponible
            $agent = $agents[0];
            if ($agent->agent_email) {
                $subject = "Nouveau chat client - Colis224";
                $message = "Le client $client_name a démarré une conversation.\n\n";
                $message .= "Conversation ID: $conversation_id\n";
                $message .= "Répondez maintenant: " . admin_url('admin.php?page=colis224-live-chat');

                wp_mail($agent->agent_email, $subject, $message);
            }
        }
    }

    /**
     * Envoyer un message
     */
    public static function send_message($conversation_id, $sender_type, $sender_id, $sender_name, $message) {
        global $wpdb;
        $table_messages = $wpdb->prefix . 'colis224_chat_messages';
        $table_conversations = $wpdb->prefix . 'colis224_chat_conversations';

        // Nettoyer le message avec Sanitizer (sans échappement excessif)
        if (class_exists('Colis224_Sanitizer')) {
            $message = Colis224_Sanitizer::clean_message($message);
        }

        // Insérer le message (wpdb s'occupe de l'échappement)
        $wpdb->insert($table_messages, array(
            'conversation_id' => $conversation_id,
            'sender_type' => $sender_type,
            'sender_id' => $sender_id,
            'sender_name' => $sender_name,
            'message' => $message,
            'is_read' => 0
        ), array('%s', '%s', '%d', '%s', '%s', '%d'));

        // Mettre à jour la conversation
        $wpdb->update($table_conversations,
            array('last_message_at' => current_time('mysql')),
            array('conversation_id' => $conversation_id)
        );

        return $wpdb->insert_id;
    }

    /**
     * Récupérer les messages d'une conversation
     */
    public static function get_messages($conversation_id, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_messages';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE conversation_id = %s ORDER BY created_at ASC LIMIT %d",
            $conversation_id,
            $limit
        ));
    }

    /**
     * Marquer les messages comme lus
     */
    public static function mark_as_read($conversation_id, $sender_type) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_messages';

        // Marquer tous les messages de l'autre partie comme lus
        $other_type = $sender_type === 'client' ? 'agent' : 'client';

        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET is_read = 1 WHERE conversation_id = %s AND sender_type = %s AND is_read = 0",
            $conversation_id,
            $other_type
        ));
    }

    /**
     * AJAX: Envoyer un message
     */
    public function ajax_send_message() {
        // Récupérer les données
        $conversation_id = sanitize_text_field($_POST['conversation_id']);
        // Ne pas utiliser sanitize_textarea_field qui échappe les apostrophes
        $message = isset($_POST['message']) ? stripslashes(wp_kses_post($_POST['message'])) : '';
        $sender_type = isset($_POST['sender_type']) ? sanitize_text_field($_POST['sender_type']) : 'client';

        if (empty($conversation_id) || empty($message)) {
            wp_send_json_error('Données manquantes');
            return;
        }

        // Déterminer l'expéditeur
        $sender_id = 0;
        $sender_name = 'Client';

        if ($sender_type === 'agent' && is_user_logged_in()) {
            $user = wp_get_current_user();
            $sender_id = $user->ID;
            $sender_name = $user->display_name;
        } elseif ($sender_type === 'client' && isset($_SESSION['colis224_client_id'])) {
            $sender_id = $_SESSION['colis224_client_id'];
            $sender_name = $_SESSION['colis224_client_name'];
        }

        // Envoyer le message
        $message_id = self::send_message($conversation_id, $sender_type, $sender_id, $sender_name, $message);

        if ($message_id) {
            wp_send_json_success(array(
                'message_id' => $message_id,
                'message' => $message,
                'sender_name' => $sender_name,
                'time' => current_time('H:i')
            ));
        } else {
            wp_send_json_error('Erreur lors de l\'envoi');
        }
    }

    /**
     * AJAX: Récupérer les messages
     */
    public function ajax_get_messages() {
        $conversation_id = sanitize_text_field($_POST['conversation_id']);
        $sender_type = isset($_POST['sender_type']) ? sanitize_text_field($_POST['sender_type']) : 'client';

        $messages = self::get_messages($conversation_id);

        // Marquer comme lu
        self::mark_as_read($conversation_id, $sender_type);

        wp_send_json_success($messages);
    }

    /**
     * AJAX: Récupérer les conversations (pour admin)
     */
    public function ajax_get_conversations() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_conversations';

        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';

        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, COUNT(m.id) as message_count, SUM(CASE WHEN m.is_read = 0 AND m.sender_type = 'client' THEN 1 ELSE 0 END) as unread_count
             FROM $table c
             LEFT JOIN {$wpdb->prefix}colis224_chat_messages m ON c.conversation_id = m.conversation_id
             WHERE c.status = %s
             GROUP BY c.id
             ORDER BY c.last_message_at DESC",
            $status
        ));

        wp_send_json_success($conversations);
    }

    /**
     * AJAX: Fermer une conversation
     */
    public function ajax_close_chat() {
        $conversation_id = sanitize_text_field($_POST['conversation_id']);

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_conversations';

        $wpdb->update($table,
            array(
                'status' => 'closed',
                'closed_at' => current_time('mysql')
            ),
            array('conversation_id' => $conversation_id)
        );

        wp_send_json_success('Conversation fermée');
    }

    /**
     * AJAX: Mettre à jour le statut de l'agent
     */
    public function ajax_update_agent_status() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Non autorisé');
            return;
        }

        $user = wp_get_current_user();
        $status = sanitize_text_field($_POST['status']);

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_agents_status';

        // Vérifier si l'agent existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE agent_id = %d",
            $user->ID
        ));

        if ($exists) {
            $wpdb->update($table,
                array(
                    'status' => $status,
                    'last_activity' => current_time('mysql')
                ),
                array('agent_id' => $user->ID)
            );
        } else {
            $wpdb->insert($table, array(
                'agent_id' => $user->ID,
                'agent_name' => $user->display_name,
                'agent_email' => $user->user_email,
                'status' => $status,
                'last_activity' => current_time('mysql')
            ));
        }

        wp_send_json_success('Statut mis à jour');
    }

    /**
     * Shortcode du widget chat (pour l'espace client)
     */
    public function chat_widget_shortcode($atts) {
        // Vérifier si le client est connecté
        if (!isset($_SESSION['colis224_client_id'])) {
            return '<p>Veuillez vous connecter pour accéder au chat.</p>';
        }

        $client_id = $_SESSION['colis224_client_id'];
        $client_name = $_SESSION['colis224_client_name'];
        $client_email = isset($_SESSION['colis224_client_email']) ? $_SESSION['colis224_client_email'] : '';
        $client_phone = isset($_SESSION['colis224_client_phone']) ? $_SESSION['colis224_client_phone'] : '';

        // Récupérer ou créer la conversation
        $conversation = self::get_or_create_conversation($client_id, $client_name, $client_email, $client_phone);

        ob_start();
        ?>
        <div class="colis224-chat-widget colis224-chat-modern" data-conversation-id="<?php echo esc_attr($conversation->conversation_id); ?>">
            <div class="chat-header">
                <h3>💬 Chat Support</h3>
                <span class="chat-status">
                    <?php if ($conversation->status === 'active'): ?>
                        <span class="status-online">● Agent en ligne</span>
                    <?php else: ?>
                        <span class="status-waiting">● En attente...</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="chat-messages" id="chat-messages-<?php echo $conversation->id; ?>">
                <!-- Messages chargés via AJAX -->
            </div>

            <div class="chat-input">
                <textarea id="chat-message-input" placeholder="Tapez votre message..." rows="1"></textarea>
                <button id="chat-send-btn" class="button button-primary colis224-btn-send">
                    <span class="btn-text">Envoyer</span>
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                    </svg>
                </button>
            </div>
            
            <!-- Indicateur de frappe -->
            <div class="colis224-typing-indicator" id="typing-indicator" style="display: none;">
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <span class="typing-text">L'agent est en train d'écrire...</span>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var conversationId = '<?php echo $conversation->conversation_id; ?>';
            var pollingInterval;

            // Charger les messages
            function loadMessages() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_get_chat_messages',
                        conversation_id: conversationId,
                        sender_type: 'client'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayMessages(response.data);
                        }
                    }
                });
            }

            // Afficher les messages
            function displayMessages(messages) {
                var container = $('#chat-messages-<?php echo $conversation->id; ?>');
                container.empty();

                messages.forEach(function(msg) {
                    var messageClass = msg.sender_type === 'client' ? 'client' : 'agent';
                    var time = new Date(msg.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});

                    // Échapper le HTML pour la sécurité mais garder les sauts de ligne
                    var messageText = $('<div>').text(msg.message).html().replace(/\n/g, '<br>');

                    var html = '<div class="chat-message ' + messageClass + '">';
                    html += '<div class="message-bubble">' + messageText + '</div>';
                    html += '<div class="message-time">' + time + '</div>';
                    html += '</div>';

                    container.append(html);
                });

                // Scroller vers le bas
                container.scrollTop(container[0].scrollHeight);
            }

            // Envoyer un message
            $('#chat-send-btn').on('click', function() {
                var message = $('#chat-message-input').val().trim();
                if (!message) return;

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_send_chat_message',
                        conversation_id: conversationId,
                        message: message,
                        sender_type: 'client'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#chat-message-input').val('');
                            loadMessages();
                        }
                    }
                });
            });

            // Enter pour envoyer
            $('#chat-message-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    $('#chat-send-btn').click();
                }
            });

            // Charger les messages au démarrage
            loadMessages();

            // Polling toutes les 3 secondes
            pollingInterval = setInterval(loadMessages, 3000);
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtenir les statistiques du chat
     */
    public static function get_chat_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_conversations';

        $stats = array();

        // Conversations actives
        $stats['active'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE status = 'active'"
        );

        // Conversations en attente
        $stats['waiting'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE status = 'waiting'"
        );

        // Total conversations aujourd'hui
        $stats['today'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE DATE(started_at) = CURRENT_DATE"
        );

        // Temps de réponse moyen (en minutes)
        $stats['avg_response_time'] = 5; // À calculer

        return $stats;
    }
}

// Initialiser
new Colis224_Live_Chat();
