<?php
/**
 * Espace Client Amélioré - Version 2.10.7
 * Intègre : Live Chat, Tickets, Fidélité, Détails colis, Notifications
 *
 * @package Colis224
 * @subpackage Client_Portal
 * @since 2.10.7
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Client_Portal_Enhanced {

    public function __construct() {
        // AJAX pour les tickets côté client
        add_action('wp_ajax_colis224_client_create_ticket', array($this, 'ajax_create_ticket'));
        add_action('wp_ajax_nopriv_colis224_client_create_ticket', array($this, 'ajax_create_ticket'));
        
        add_action('wp_ajax_colis224_client_get_tickets', array($this, 'ajax_get_tickets'));
        add_action('wp_ajax_nopriv_colis224_client_get_tickets', array($this, 'ajax_get_tickets'));
        
        add_action('wp_ajax_colis224_client_get_ticket_conversation', array($this, 'ajax_get_ticket_conversation'));
        add_action('wp_ajax_nopriv_colis224_client_get_ticket_conversation', array($this, 'ajax_get_ticket_conversation'));
        
        add_action('wp_ajax_colis224_client_reply_ticket', array($this, 'ajax_reply_ticket'));
        add_action('wp_ajax_nopriv_colis224_client_reply_ticket', array($this, 'ajax_reply_ticket'));
        
        // AJAX pour les détails de colis
        add_action('wp_ajax_colis224_client_get_parcel_details', array($this, 'ajax_get_parcel_details'));
        add_action('wp_ajax_nopriv_colis224_client_get_parcel_details', array($this, 'ajax_get_parcel_details'));
        
        // AJAX pour le live chat côté client
        add_action('wp_ajax_colis224_client_send_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_nopriv_colis224_client_send_message', array($this, 'ajax_send_message'));
        
        add_action('wp_ajax_colis224_client_get_messages', array($this, 'ajax_get_messages'));
        add_action('wp_ajax_nopriv_colis224_client_get_messages', array($this, 'ajax_get_messages'));
        
        add_action('wp_ajax_colis224_client_get_unread_count', array($this, 'ajax_get_unread_count'));
        add_action('wp_ajax_nopriv_colis224_client_get_unread_count', array($this, 'ajax_get_unread_count'));
        
        // AJAX pour créer un colis depuis l'espace client
        add_action('wp_ajax_colis224_client_create_parcel', array($this, 'ajax_create_parcel'));
        add_action('wp_ajax_nopriv_colis224_client_create_parcel', array($this, 'ajax_create_parcel'));
        
        // AJAX pour créer un client depuis l'espace client
        add_action('wp_ajax_colis224_client_create_client', array($this, 'ajax_create_client'));
        add_action('wp_ajax_nopriv_colis224_client_create_client', array($this, 'ajax_create_client'));
        
        // AJAX pour créer un départ depuis l'espace client
        add_action('wp_ajax_colis224_client_create_departure', array($this, 'ajax_create_departure'));
        add_action('wp_ajax_nopriv_colis224_client_create_departure', array($this, 'ajax_create_departure'));
        
        // AJAX pour rechercher des clients existants
        add_action('wp_ajax_colis224_client_search_clients', array($this, 'ajax_search_clients'));
        add_action('wp_ajax_nopriv_colis224_client_search_clients', array($this, 'ajax_search_clients'));
    }

    /**
     * Helper: Vérifier l'authentification du client pour les requêtes AJAX
     * @return int|false ID du client si connecté, false sinon
     */
    private function verify_client_ajax_auth() {
        // Vérifier si le client est connecté via WordPress
        if (!Colis224_WP_User_Sync::is_client_logged_in()) {
            return false;
        }

        // Récupérer l'ID du client
        $client_id = Colis224_WP_User_Sync::get_current_client_id();

        if (!$client_id) {
            return false;
        }

        return $client_id;
    }

    /**
     * Afficher l'espace client amélioré dans le shortcode
     */
    public static function render_enhanced_portal($client_id) {
        global $wpdb;

        // CRITIQUE: S'assurer que jQuery est chargé pour le JavaScript inline
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }

        // SÉCURITÉ: Vérifier les permissions de manière défensive
        // Vérifie si l'utilisateur peut créer des colis/clients (admins, agents, éditeurs, auteurs)
        $can_create_items = false;
        if (function_exists('is_user_logged_in') && function_exists('current_user_can')) {
            if (is_user_logged_in()) {
                $can_create_items = current_user_can('colis224_create_parcel') || current_user_can('manage_options');
            }
        }

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_clients WHERE id = %d",
            $client_id
        ));

        if (!$client) {
            return '<p>Client introuvable.</p>';
        }

        // Récupérer les colis du client
        $parcels = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_parcels
            WHERE client_id = %d
            ORDER BY created_at DESC
            LIMIT 20",
            $client_id
        ));

        // Récupérer les informations de fidélité
        $loyalty_info = self::get_client_loyalty_info($client_id);

        // Récupérer les tickets
        $tickets = self::get_client_tickets($client_id);

        // Compter les messages non lus
        $unread_count = self::get_unread_messages_count($client_id);

        ob_start();
        ?>
        <div class="colis224-enhanced-portal" id="colis224-client-portal">
            
            <!-- Header avec notifications -->
            <div class="colis224-portal-header-enhanced">
                <div class="portal-welcome">
                    <h2>👋 Bienvenue, <?php echo esc_html($client->name); ?></h2>
                    <p class="client-phone">📞 <?php echo esc_html($client->phone); ?></p>
                </div>
                
                <div class="portal-header-actions">
                    <!-- Notification Badge -->
                    <?php if ($unread_count > 0): ?>
                    <div class="notification-badge">
                        <span class="dashicons dashicons-bell"></span>
                        <span class="badge-count"><?php echo $unread_count; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Bouton Chat Flottant -->
                    <button id="btn-toggle-chat" class="btn-chat-toggle" title="Live Chat">
                        <span class="dashicons dashicons-format-chat"></span>
                        💬 Chat
                        <?php if ($unread_count > 0): ?>
                        <span class="chat-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Déconnexion -->
                    <?php
                    // Obtenir l'URL de déconnexion avec redirection vers page de connexion
                    // Compatible WPS Hide Login
                    $logout_redirect = get_permalink();

                    // Si WPS Hide Login est actif, rediriger vers la page de connexion personnalisée
                    if (function_exists('wps_hide_login_get_page')) {
                        $custom_login_slug = get_option('whl_page', 'login');
                        if ($custom_login_slug) {
                            $logout_redirect = home_url($custom_login_slug);
                        }
                    }
                    ?>
                    <a href="<?php echo wp_logout_url($logout_redirect); ?>" class="btn-logout">
                        <span class="dashicons dashicons-exit"></span> Déconnexion
                    </a>
                </div>
            </div>

            <!-- Programme de Fidélité -->
            <?php if ($loyalty_info): ?>
            <div class="loyalty-card-enhanced">
                <div class="loyalty-header">
                    <h3>🎁 Programme de Fidélité</h3>
                    <span class="loyalty-level level-<?php echo esc_attr(strtolower($loyalty_info['level'])); ?>">
                        <?php echo esc_html($loyalty_info['level']); ?>
                    </span>
                </div>
                
                <div class="loyalty-stats">
                    <div class="loyalty-stat">
                        <span class="stat-icon">⭐</span>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $loyalty_info['points']; ?></span>
                            <span class="stat-label">Points</span>
                        </div>
                    </div>
                    
                    <div class="loyalty-stat">
                        <span class="stat-icon">📦</span>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $loyalty_info['total_parcels']; ?></span>
                            <span class="stat-label">Colis envoyés</span>
                        </div>
                    </div>
                    
                    <div class="loyalty-stat">
                        <span class="stat-icon">💰</span>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo number_format($loyalty_info['total_spent'], 0, ',', ' '); ?> GNF</span>
                            <span class="stat-label">Total dépensé</span>
                        </div>
                    </div>
                </div>

                <?php if ($loyalty_info['next_level']): ?>
                <div class="loyalty-progress">
                    <p class="progress-text">
                        Plus que <strong><?php echo $loyalty_info['points_to_next']; ?> points</strong> 
                        pour atteindre le niveau <strong><?php echo $loyalty_info['next_level']; ?></strong> !
                    </p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $loyalty_info['progress_percent']; ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="loyalty-encouragement">
                    <p>💪 Continue d'utiliser Colis224 pour gagner plus de points et profiter d'avantages exclusifs !</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prochains Départs -->
            <?php echo self::render_upcoming_departures(); ?>

            <!-- Onglets Navigation -->
            <div class="portal-tabs">
                <button class="tab-btn active" data-tab="parcels">
                    <span class="dashicons dashicons-archive"></span> Mes Colis
                </button>
                <?php
                // Onglet "Ajouter" réservé aux admins, agents, éditeurs et auteurs UNIQUEMENT
                if ($can_create_items):
                ?>
                <button class="tab-btn" data-tab="add">
                    <span class="dashicons dashicons-plus-alt"></span> Ajouter
                </button>
                <?php endif; ?>
                <?php
                // Onglet "Tarif" visible pour les agents, admins et éditeurs
                if ($can_create_items):
                ?>
                <button class="tab-btn" data-tab="tarif">
                    <span class="dashicons dashicons-money-alt"></span> Tarif
                </button>
                <?php endif; ?>
                <button class="tab-btn" data-tab="tickets">
                    <span class="dashicons dashicons-tickets-alt"></span> Support (<?php echo count($tickets); ?>)
                </button>
                <button class="tab-btn" data-tab="help">
                    <span class="dashicons dashicons-sos"></span> Aide
                </button>
            </div>

            <!-- Contenu des Onglets -->
            <div class="portal-content">
                
                <!-- ONGLET: Mes Colis -->
                <div class="tab-content active" id="tab-parcels">
                    <h3>📦 Mes Expéditions</h3>
                    
                    <?php if (empty($parcels)): ?>
                        <div class="empty-state">
                            <span class="dashicons dashicons-inbox"></span>
                            <p>Vous n'avez pas encore de colis enregistré.</p>
                        </div>
                    <?php else: ?>
                        <div class="parcels-grid">
                            <?php foreach ($parcels as $parcel): ?>
                            <div class="parcel-card" data-parcel-id="<?php echo $parcel->id; ?>">
                                <div class="parcel-header">
                                    <span class="tracking-number">📍 <?php echo esc_html($parcel->tracking_number); ?></span>
                                    <span class="parcel-status status-<?php echo esc_attr(self::get_status_class($parcel->status)); ?>">
                                        <?php echo esc_html($parcel->status); ?>
                                    </span>
                                </div>
                                
                                <div class="parcel-info">
                                    <p><strong>Destinataire:</strong> <?php echo esc_html($parcel->recipient_name); ?></p>
                                    <p><strong>Téléphone:</strong> <?php echo esc_html($parcel->recipient_phone); ?></p>
                                    <p><strong>Poids:</strong> <?php echo number_format($parcel->weight, 2); ?> kg</p>
                                    <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($parcel->created_at)); ?></p>
                                </div>
                                
                                <button class="btn-view-details" data-parcel-id="<?php echo $parcel->id; ?>">
                                    <span class="dashicons dashicons-visibility"></span> Voir les détails
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ONGLET: Ajouter (Réservé aux admins/agents uniquement) -->
                <?php if ($can_create_items): ?>
                <div class="tab-content" id="tab-add">
                    <div class="add-actions-tabs">
                        <button class="sub-tab-btn active" data-subtab="add-parcel">
                            <span class="dashicons dashicons-archive"></span> Ajouter un Colis
                        </button>
                        <button class="sub-tab-btn" data-subtab="add-client">
                            <span class="dashicons dashicons-groups"></span> Ajouter un Client
                        </button>
                        <button class="sub-tab-btn" data-subtab="add-departure">
                            <span class="dashicons dashicons-airplane"></span> Ajouter un Départ
                        </button>
                    </div>

                    <!-- Sous-onglet: Ajouter un Colis -->
                    <div class="sub-tab-content active" id="subtab-add-parcel">
                        <h3>📦 Nouveau Colis</h3>
                        <form id="form-add-parcel" class="client-form">
                            <div class="form-group">
                                <label>Rechercher un client existant (optionnel)</label>
                                <input type="text" id="client-search-input" placeholder="Tapez le nom ou téléphone du client..." autocomplete="off">
                                <input type="hidden" id="selected-client-id" name="client_id">
                                <div id="client-search-results" class="search-results"></div>
                            </div>

                            <div class="form-group">
                                <label>Nom du destinataire *</label>
                                <input type="text" name="recipient_name" required>
                            </div>

                            <div class="form-group">
                                <label>Téléphone du destinataire *</label>
                                <input type="text" name="recipient_phone" required>
                            </div>

                            <div class="form-group">
                                <label>Adresse du destinataire *</label>
                                <textarea name="recipient_address" rows="3" required></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Poids (kg) *</label>
                                    <input type="number" name="weight" step="0.01" required>
                                </div>

                                <div class="form-group">
                                    <label>Montant total (GNF) *</label>
                                    <input type="number" name="total_amount" step="0.01" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Notes (optionnel)</label>
                                <textarea name="notes" rows="2"></textarea>
                            </div>

                            <button type="submit" class="btn-primary">
                                <span class="dashicons dashicons-yes"></span> Créer le Colis
                            </button>
                        </form>
                    </div>

                    <!-- Sous-onglet: Ajouter un Client -->
                    <div class="sub-tab-content" id="subtab-add-client">
                        <h3>👤 Nouveau Client</h3>
                        <form id="form-add-client" class="client-form">
                            <div class="form-group">
                                <label>Type *</label>
                                <select name="type" required>
                                    <option value="Particulier">Particulier</option>
                                    <option value="Entreprise">Entreprise</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Nom / Contact *</label>
                                <input type="text" name="name" required>
                            </div>

                            <div class="form-group">
                                <label>Raison Sociale (si entreprise)</label>
                                <input type="text" name="company_name">
                            </div>

                            <div class="form-group">
                                <label>Téléphone *</label>
                                <input type="text" name="phone" required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email">
                            </div>

                            <div class="form-group">
                                <label>Adresse</label>
                                <textarea name="address" rows="2"></textarea>
                            </div>

                            <button type="submit" class="btn-primary">
                                <span class="dashicons dashicons-yes"></span> Créer le Client
                            </button>
                        </form>
                    </div>

                    <!-- Sous-onglet: Ajouter un Départ -->
                    <div class="sub-tab-content" id="subtab-add-departure">
                        <h3>🚀 Nouveau Départ</h3>
                        <form id="form-add-departure" class="client-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ville de départ *</label>
                                    <input type="text" name="departure_city" required>
                                </div>

                                <div class="form-group">
                                    <label>Pays de départ *</label>
                                    <select name="departure_country_code" required>
                                        <option value="">-- Sélectionner --</option>
                                        <option value="GN">🇬🇳 Guinée</option>
                                        <option value="FR">🇫🇷 France</option>
                                        <option value="CN">🇨🇳 Chine</option>
                                        <option value="US">🇺🇸 États-Unis</option>
                                        <option value="GB">🇬🇧 Royaume-Uni</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ville d'arrivée *</label>
                                    <input type="text" name="arrival_city" required>
                                </div>

                                <div class="form-group">
                                    <label>Pays d'arrivée *</label>
                                    <select name="arrival_country_code" required>
                                        <option value="">-- Sélectionner --</option>
                                        <option value="GN">🇬🇳 Guinée</option>
                                        <option value="FR">🇫🇷 France</option>
                                        <option value="CN">🇨🇳 Chine</option>
                                        <option value="US">🇺🇸 États-Unis</option>
                                        <option value="GB">🇬🇧 Royaume-Uni</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date de départ *</label>
                                    <input type="date" name="departure_date" required>
                                </div>

                                <div class="form-group">
                                    <label>Heure de départ</label>
                                    <input type="time" name="departure_time" value="08:00">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Type de transport *</label>
                                <label style="margin-right: 20px;">
                                    <input type="radio" name="transport_type" value="plane" checked> ✈️ Avion
                                </label>
                                <label>
                                    <input type="radio" name="transport_type" value="boat"> 🚢 Bateau
                                </label>
                            </div>

                            <div class="form-group">
                                <label>Notes (optionnel)</label>
                                <textarea name="notes" rows="2"></textarea>
                            </div>

                            <button type="submit" class="btn-primary">
                                <span class="dashicons dashicons-yes"></span> Créer le Départ
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; // Fin condition permissions Ajouter ?>

                <!-- ONGLET: Tarif (Réservé aux admins/agents uniquement) -->
                <?php if ($can_create_items): ?>
                <div class="tab-content" id="tab-tarif">
                    <h3>💰 Calculateur de Tarifs Colis224</h3>
                    <p class="subtitle" style="margin-bottom: 24px;">Obtenez vos tarifs et shipping marks en quelques clics</p>

                    <?php
                    // Charger le calculateur frontend
                    if (class_exists('Colis224_Frontend_Calculator')) {
                        // Forcer l'enqueue des scripts du calculateur
                        wp_enqueue_style(
                            'colis224-frontend-calculator',
                            COLIS224_PLUGIN_URL . 'assets/css/frontend-calculator.css',
                            array(),
                            COLIS224_VERSION
                        );

                        wp_enqueue_script(
                            'colis224-frontend-calculator',
                            COLIS224_PLUGIN_URL . 'assets/js/frontend-calculator.js',
                            array('jquery'),
                            COLIS224_VERSION . '.' . time(),
                            true
                        );

                        // Localize script avec les données nécessaires
                        wp_localize_script('colis224-frontend-calculator', 'colis224Frontend', array(
                            'ajax_url' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('colis224_frontend_nonce'),
                            'warehouse_phone_china' => '+8618719472926',
                            'warehouse_phone_guinea' => '+224620178930',
                            'agencies' => array(
                                array('name' => 'Agence 1', 'phone' => '+224620178930'),
                                array('name' => 'Agence 2 Lambanyi', 'phone' => '+224626526737'),
                                array('name' => 'Agence 3', 'phone' => '+224626526735'),
                                array('name' => 'Bureau France', 'phone' => '+33698485752'),
                                array('name' => 'Agence USA', 'phone' => '+17185822079'),
                            ),
                            'orange_money_code' => '#144*6*649048*100000*code secret#OK',
                            'orange_money_merchant' => 'COLIS224',
                            'orange_money_qr' => 'https://colis224.com/wp-content/uploads/2026/01/compte-marchand-orange-money-colis224.jpg',
                            'wechat_qr' => 'https://colis224.com/wp-content/uploads/2026/01/Compte-Wechat.jpg',
                            'images' => array(
                                'sea_cargo' => 'https://colis224.com/wp-content/uploads/2026/01/adresse-bateau.jpeg',
                                'air_plane' => 'https://colis224.com/wp-content/uploads/2026/01/adrsse-avion.jpeg',
                                'air_cargo_mark' => 'https://colis224.com/wp-content/uploads/2026/01/Process-avion.jpg',
                                'sea_cargo_mark' => 'https://colis224.com/wp-content/uploads/2026/01/process-bateau.jpg',
                            )
                        ));

                        // Afficher le calculateur
                        echo do_shortcode('[colis224_calculator]');
                    } else {
                        echo '<div class="empty-state">';
                        echo '<span class="dashicons dashicons-warning"></span>';
                        echo '<p>Le calculateur de tarifs n\'est pas disponible pour le moment.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <?php endif; // Fin condition permissions Tarif ?>

                <!-- ONGLET: Support / Tickets -->
                <div class="tab-content" id="tab-tickets">
                    <div class="tickets-header">
                        <h3>🎫 Mes Tickets de Support</h3>
                        <button id="btn-new-ticket" class="btn-primary">
                            <span class="dashicons dashicons-plus-alt"></span> Nouveau Ticket
                        </button>
                    </div>

                    <!-- Formulaire Nouveau Ticket (caché par défaut) -->
                    <div id="new-ticket-form" class="ticket-form" style="display: none;">
                        <h4>Créer un nouveau ticket</h4>
                        <form id="form-create-ticket">
                            <div class="form-group">
                                <label>Sujet *</label>
                                <input type="text" name="subject" required placeholder="Ex: Problème avec mon colis">
                            </div>
                            <div class="form-group">
                                <label>Message *</label>
                                <textarea name="message" rows="4" required placeholder="Décrivez votre demande..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Envoyer</button>
                                <button type="button" class="btn-secondary" id="btn-cancel-ticket">Annuler</button>
                            </div>
                        </form>
                    </div>

                    <!-- Liste des tickets -->
                    <div id="tickets-list">
                        <?php if (empty($tickets)): ?>
                            <div class="empty-state">
                                <span class="dashicons dashicons-tickets-alt"></span>
                                <p>Vous n'avez pas encore de ticket de support.</p>
                                <p class="help-text">Cliquez sur "Nouveau Ticket" pour contacter notre équipe.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                            <div class="ticket-item" data-ticket-id="<?php echo $ticket->id; ?>">
                                <div class="ticket-header">
                                    <span class="ticket-id">#<?php echo $ticket->id; ?></span>
                                    <span class="ticket-status status-<?php echo esc_attr($ticket->status); ?>">
                                        <?php echo esc_html(ucfirst($ticket->status)); ?>
                                    </span>
                                </div>
                                <h4 class="ticket-subject"><?php echo esc_html($ticket->subject); ?></h4>
                                <p class="ticket-date">📅 <?php echo date('d/m/Y H:i', strtotime($ticket->created_at)); ?></p>
                                <button class="btn-view-ticket" data-ticket-id="<?php echo $ticket->id; ?>">
                                    Voir la conversation →
                                </button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ONGLET: Aide -->
                <div class="tab-content" id="tab-help">
                    <h3>❓ Centre d'Aide</h3>
                    
                    <div class="help-sections">
                        <div class="help-section">
                            <h4>💬 Comment utiliser le Live Chat ?</h4>
                            <p>Cliquez sur l'icône de chat en haut à droite pour ouvrir la fenêtre de discussion. Un agent vous répondra dans les plus brefs délais.</p>
                        </div>

                        <div class="help-section">
                            <h4>🎫 Comment ouvrir un ticket ?</h4>
                            <p>Allez dans l'onglet "Support", cliquez sur "Nouveau Ticket", remplissez le formulaire et envoyez. Vous recevrez une réponse par email.</p>
                        </div>

                        <div class="help-section">
                            <h4>📦 Comment suivre mon colis ?</h4>
                            <p>Dans l'onglet "Mes Colis", cliquez sur "Voir les détails" pour suivre l'état de votre expédition en temps réel.</p>
                        </div>

                        <div class="help-section">
                            <h4>⭐ Comment fonctionne le programme de fidélité ?</h4>
                            <p>Gagnez des points à chaque envoi de colis. Plus vous envoyez, plus vous montez de niveau et profitez d'avantages exclusifs !</p>
                        </div>

                        <div class="help-section">
                            <h4>📞 Besoin d'aide urgente ?</h4>
                            <p><strong>Agence Guinée:</strong> +224 626 526 735</p>
                            <p><strong>Service International:</strong> +224 620 178 930</p>
                            <p><strong>Email:</strong> contact@colis224.com</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Widget Live Chat Flottant -->
            <div id="live-chat-widget" class="chat-widget" style="display: none;">
                <div class="chat-header">
                    <div class="chat-title">
                        <span class="dashicons dashicons-format-chat"></span>
                        <span>Live Chat</span>
                    </div>
                    <button id="btn-close-chat" class="btn-close-chat">×</button>
                </div>

                <div class="chat-messages" id="chat-messages">
                    <div class="chat-welcome">
                        <p>👋 Bonjour ! Comment pouvons-nous vous aider ?</p>
                    </div>
                </div>

                <div class="chat-input">
                    <textarea id="chat-message-input" placeholder="Tapez votre message..." rows="2"></textarea>
                    <button id="btn-send-message" class="btn-send">
                        <span>Envoyer</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Détails Colis -->
            <div id="parcel-details-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>📦 Détails du Colis</h3>
                        <button class="btn-close-modal">×</button>
                    </div>
                    <div class="modal-body" id="parcel-details-content">
                        <div class="loading">Chargement...</div>
                    </div>
                </div>
            </div>

            <!-- Modal Conversation Ticket -->
            <div id="ticket-conversation-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>🎫 Conversation Ticket</h3>
                        <button class="btn-close-modal">×</button>
                    </div>
                    <div class="modal-body" id="ticket-conversation-content">
                        <div class="loading">Chargement...</div>
                    </div>
                </div>
            </div>

        </div>

        <style>
        /* Styles pour l'espace client amélioré - Injecté inline pour simplifier */
        .colis224-enhanced-portal {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .colis224-portal-header-enhanced {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .portal-welcome h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .client-phone {
            margin: 0;
            opacity: 0.9;
        }

        .portal-header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .notification-badge {
            position: relative;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
        }

        .badge-count, .chat-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }

        .btn-chat-toggle {
            position: relative;
            background: #25D366;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-chat-toggle:hover {
            background: #128C7E;
            transform: scale(1.05);
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Carte de Fidélité */
        .loyalty-card-enhanced {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 2px solid #667eea;
        }

        .loyalty-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .loyalty-level {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .level-bronze { background: #CD7F32; color: white; }
        .level-argent { background: #C0C0C0; color: #333; }
        .level-or { background: #FFD700; color: #333; }
        .level-platine { background: linear-gradient(135deg, #E5E4E2 0%, #BCC6CC 100%); color: #333; }

        .loyalty-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .loyalty-stat {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat-icon {
            font-size: 32px;
        }

        .stat-value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            display: block;
            font-size: 13px;
            color: #666;
        }

        .loyalty-progress {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border-radius: 10px;
        }

        .progress-bar {
            height: 10px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
        }

        .loyalty-encouragement {
            margin-top: 15px;
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 5px;
        }

        .loyalty-encouragement p {
            margin: 0;
            color: #856404;
        }

        /* Onglets */
        .portal-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-btn {
            background: transparent;
            border: none;
            padding: 15px 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 15px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Grille de colis */
        .parcels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .parcel-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
        }

        .parcel-card:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-5px);
        }

        .parcel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .tracking-number {
            font-weight: bold;
            color: #667eea;
            font-size: 16px;
        }

        .parcel-status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-en-attente { background: #fff3cd; color: #856404; }
        .status-expedie { background: #cfe2ff; color: #084298; }
        .status-en-transit { background: #d1e7dd; color: #0f5132; }
        .status-livre { background: #d1e7dd; color: #0f5132; }
        .status-retour { background: #f8d7da; color: #842029; }

        .parcel-info p {
            margin: 8px 0;
            font-size: 14px;
            color: #666;
        }

        .btn-view-details {
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .btn-view-details:hover {
            background: #5568d3;
        }

        /* Tickets */
        .tickets-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .ticket-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
        }

        .ticket-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 15px;
        }

        .ticket-id {
            font-weight: bold;
            color: #667eea;
        }

        .ticket-subject {
            margin: 10px 0;
            font-size: 18px;
        }

        .btn-view-ticket {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .btn-view-ticket:hover {
            background: #667eea;
            color: white;
        }

        /* Live Chat Widget */
        .chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            max-height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 9999;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
        }

        .btn-close-chat {
            background: rgba(255, 255, 255, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.6);
            color: white;
            font-size: 32px;
            font-weight: 900;
            line-height: 1;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            font-family: Arial, sans-serif;
        }

        .btn-close-chat:hover {
            background: rgba(255, 255, 255, 0.5);
            border-color: rgba(255, 255, 255, 0.9);
            transform: rotate(90deg) scale(1.15);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            max-height: 350px;
        }

        .chat-welcome {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .chat-input {
            padding: 15px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
        }

        .chat-input textarea {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            resize: none;
        }

        .btn-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .btn-send:active {
            transform: translateY(0);
        }

        .btn-send svg {
            flex-shrink: 0;
        }

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
            background-color: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%);
            border-radius: 28px;
            max-width: 900px;
            width: 90%;
            max-height: 85vh;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(102, 126, 234, 0.1),
                0 32px 64px -12px rgba(0, 0, 0, 0.3),
                0 8px 24px -8px rgba(102, 126, 234, 0.2);
            animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .modal-header {
            padding: 28px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 28px 28px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .modal-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        .modal-header h3 {
            margin: 0;
            position: relative;
            z-index: 1;
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .modal-body {
            padding: 35px;
            overflow-y: auto;
            max-height: calc(85vh - 140px);
            scrollbar-width: thin;
            scrollbar-color: rgba(102, 126, 234, 0.3) transparent;
        }

        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.3);
            border-radius: 10px;
            transition: background 0.3s;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.5);
        }

        .btn-close-modal {
            background: rgba(255, 255, 255, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.5);
            color: white;
            font-size: 36px;
            font-weight: 800;
            line-height: 0.75;
            width: 48px;
            height: 48px;
            padding: 0;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .btn-close-modal:hover {
            background: rgba(255, 255, 255, 0.5);
            border-color: rgba(255, 255, 255, 0.8);
            transform: rotate(90deg) scale(1.15);
            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        .btn-close-modal:active {
            transform: rotate(90deg) scale(1.05);
        }

        .btn-close-modal .dashicons {
            font-size: 28px;
            width: 28px;
            height: 28px;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }

        @keyframes modalSlideUp {
            0% {
                opacity: 0;
                transform: translateY(60px) scale(0.95);
            }
            50% {
                opacity: 1;
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state .dashicons {
            font-size: 80px;
            opacity: 0.3;
        }

        .help-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .help-section h4 {
            margin-top: 0;
            color: #667eea;
        }

        /* Sous-onglets */
        .add-actions-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e0e0e0;
        }

        .sub-tab-btn {
            background: transparent;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .sub-tab-btn:hover {
            color: #667eea;
        }

        .sub-tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .sub-tab-content {
            display: none;
        }

        .sub-tab-content.active {
            display: block;
        }

        /* Formulaires */
        .client-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            max-width: 800px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Recherche de clients (amélioration v2.18.21) */
        .search-results {
            position: relative;
            margin-top: 10px;
        }

        .client-search-header {
            padding: 10px 15px;
            background: #667eea;
            color: white;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
            font-size: 13px;
        }

        .client-search-list {
            list-style: none;
            padding: 0;
            margin: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-height: 300px;
            overflow-y: auto;
        }

        .client-search-list li {
            padding: 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .client-search-list li:hover {
            background: #f0f4ff;
            transform: translateX(5px);
            border-left: 3px solid #667eea;
        }

        .client-search-list li:last-child {
            border-bottom: none;
        }

        .client-search-list li.selected-client {
            background: #e8f5e9;
            border-left: 3px solid #4caf50;
        }

        .client-info {
            flex: 1;
        }

        .client-info strong {
            display: block;
            color: #333;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .client-details {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .client-details span {
            font-size: 13px;
            color: #666;
        }

        .select-btn {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
            transition: all 0.3s;
        }

        .client-search-list li:hover .select-btn {
            background: #5568d3;
            transform: scale(1.05);
        }

        .no-results, .searching-clients, .search-error {
            padding: 20px;
            text-align: center;
            margin: 0;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .no-results {
            color: #999;
            background: #f9f9f9;
        }

        .searching-clients {
            color: #667eea;
            background: #f0f4ff;
            border-color: #667eea;
        }

        .search-error {
            color: #d32f2f;
            background: #ffebee;
            border-color: #d32f2f;
        }

        .rotating {
            animation: rotate 1s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive Mobile */
        @media (max-width: 768px) {
            /* En-tête */
            .colis224-portal-header-enhanced {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .portal-welcome h2 {
                font-size: 20px;
            }

            .portal-header-actions {
                flex-wrap: wrap;
                gap: 10px;
                width: 100%;
                justify-content: center;
            }

            .btn-logout {
                padding: 8px 15px;
                font-size: 14px;
            }

            /* Onglets */
            .portal-tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                gap: 5px;
                padding: 0 10px;
            }

            .tab-btn {
                padding: 10px 15px;
                font-size: 13px;
                white-space: nowrap;
                flex-shrink: 0;
            }

            /* Grille des colis */
            .parcels-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .parcel-card {
                padding: 15px;
            }

            /* Fidélité */
            .loyalty-card-enhanced {
                padding: 15px;
            }

            .loyalty-stats {
                flex-direction: column;
                gap: 10px;
            }

            .loyalty-stat {
                flex: 1;
                min-width: unset;
            }

            /* Formulaires */
            .form-row {
                grid-template-columns: 1fr;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 16px; /* Évite le zoom sur iOS */
            }

            /* Tickets */
            .ticket-item {
                padding: 15px;
            }

            .ticket-subject {
                font-size: 16px;
            }

            /* Onglets d'ajout */
            .add-actions-tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                gap: 5px;
            }

            .add-tab-btn {
                padding: 8px 15px;
                font-size: 13px;
                white-space: nowrap;
            }

            /* Chat widget */
            .chat-widget {
                width: calc(100% - 20px);
                right: 10px;
                bottom: 10px;
                max-height: 80vh;
            }

            .chat-header {
                padding: 12px;
            }

            .chat-messages {
                max-height: 50vh;
            }

            /* Modales */
            .modal-content {
                width: 95%;
                margin: 5% auto;
                max-height: 90vh;
                overflow-y: auto;
            }

            .modal-header h3 {
                font-size: 18px;
            }

            /* Statistiques */
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            /* Badges */
            .status-badge,
            .parcel-status {
                font-size: 11px;
                padding: 4px 10px;
            }

            /* Boutons */
            .btn-primary,
            .btn-secondary {
                width: 100%;
                padding: 12px;
                font-size: 14px;
            }

            .btn-view-details,
            .btn-view-ticket {
                padding: 10px;
                font-size: 14px;
            }
        }

        /* Très petits écrans */
        @media (max-width: 480px) {
            .colis224-enhanced-portal {
                padding: 10px;
            }

            .portal-welcome h2 {
                font-size: 18px;
            }

            .client-phone {
                font-size: 13px;
            }

            .tab-btn {
                padding: 8px 12px;
                font-size: 12px;
            }

            .parcel-card {
                padding: 12px;
            }

            .tracking-number {
                font-size: 14px;
            }

            .loyalty-card-enhanced {
                padding: 12px;
            }

            .modal-content {
                width: 98%;
                margin: 1% auto;
            }

            .chat-widget {
                width: calc(100% - 10px);
                right: 5px;
                bottom: 5px;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            console.log('✅ Espace client amélioré chargé');
            console.log('Bouton chat trouvé:', $('#btn-toggle-chat').length > 0);
            
            // Navigation par onglets
            $('.tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $('#tab-' + tab).addClass('active');
            });

            // Navigation par sous-onglets
            $('.sub-tab-btn').on('click', function() {
                var subtab = $(this).data('subtab');
                $('.sub-tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.sub-tab-content').removeClass('active');
                $('#subtab-' + subtab).addClass('active');
            });

            // Recherche de clients (amélioration v2.18.21, v2.18.24: ultra-optimisée)
            var searchTimeout;
            var clientSearchCache = {}; // Cache local JavaScript pour résultats instantanés

            // Charger le cache depuis localStorage
            try {
                var storedCache = localStorage.getItem('colis224_client_cache');
                if (storedCache) {
                    var parsed = JSON.parse(storedCache);
                    // Vérifier si le cache n'est pas expiré (5 minutes)
                    if (parsed.timestamp && (Date.now() - parsed.timestamp) < 300000) {
                        clientSearchCache = parsed.data;
                    }
                }
            } catch(e) {}

            $('#client-search-input').on('input', function() {
                var searchTerm = $(this).val().trim();
                clearTimeout(searchTimeout);

                if (searchTerm.length < 2) {
                    $('#client-search-results').empty();
                    $('#selected-client-id').val('');
                    return;
                }

                // v2.18.24: Vérifier d'abord le cache local (instantané !)
                if (clientSearchCache[searchTerm]) {
                    var cachedData = clientSearchCache[searchTerm];
                    displaySearchResults(cachedData, searchTerm);
                    return;
                }

                // Afficher indicateur de chargement
                $('#client-search-results').html('<p class="searching-clients"><span class="dashicons dashicons-update-alt rotating"></span> Recherche...</p>');

                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'colis224_client_search_clients',
                            term: searchTerm,
                            nonce: '<?php echo wp_create_nonce('colis224_client_portal'); ?>'
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                // Sauvegarder dans le cache local
                                clientSearchCache[searchTerm] = response.data;

                                // Sauvegarder dans localStorage
                                try {
                                    localStorage.setItem('colis224_client_cache', JSON.stringify({
                                        timestamp: Date.now(),
                                        data: clientSearchCache
                                    }));
                                } catch(e) {}

                                displaySearchResults(response.data, searchTerm);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Erreur recherche clients:', error);
                            $('#client-search-results').html('<p class="search-error">⚠️ Erreur de connexion. Réessayez.</p>');
                        }
                    });
                }, 50); // v2.18.24: Réduit à 50ms (était 400ms puis 150ms) - 88% plus rapide !
            });

            // Fonction pour afficher les résultats de recherche
            function displaySearchResults(data, searchTerm) {
                if (data && data.length > 0) {
                    var html = '<div class="client-search-header">📋 ' + data.length + ' client(s) trouvé(s)</div>';
                    html += '<ul class="client-search-list">';
                    data.forEach(function(client) {
                        html += '<li data-client-id="' + client.id + '" data-client-name="' + client.name + '" data-client-phone="' + client.phone + '">';
                        html += '<div class="client-info">';
                        html += '<strong>👤 ' + client.name + '</strong>';
                        html += '<div class="client-details">';
                        html += '<span>📞 ' + client.phone + '</span>';
                        if (client.email) html += '<span>✉️ ' + client.email + '</span>';
                        html += '</div>';
                        html += '</div>';
                        html += '<span class="select-btn">Sélectionner ➜</span>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    $('#client-search-results').html(html);
                } else {
                    $('#client-search-results').html('<p class="no-results">❌ Aucun client trouvé pour "' + searchTerm + '"</p>');
                }
            }

            // Sélection d'un client depuis les résultats (amélioration v2.18.21)
            $(document).on('click', '.client-search-list li', function() {
                var clientId = $(this).data('client-id');
                var clientName = $(this).data('client-name');
                var clientPhone = $(this).data('client-phone');

                // Remplir les champs
                $('#selected-client-id').val(clientId);
                $('#client-search-input').val('✅ ' + clientName + ' (' + clientPhone + ')');
                $('#client-search-input').css('background-color', '#e8f5e9');

                // Vider les résultats
                $('#client-search-results').empty();

                // Feedback visuel
                $(this).addClass('selected-client');
            });

            // Réinitialiser la sélection quand on modifie le champ
            $('#client-search-input').on('focus', function() {
                if ($('#selected-client-id').val()) {
                    $(this).val('');
                    $(this).css('background-color', '');
                    $('#selected-client-id').val('');
                }
            });

            // Soumission formulaire ajouter colis
            $('#form-add-parcel').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                formData += '&action=colis224_client_create_parcel&nonce=<?php echo wp_create_nonce('colis224_client_portal'); ?>';

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        $('#form-add-parcel button[type="submit"]').prop('disabled', true).text('Création...');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Colis créé avec succès ! Numéro de suivi: ' + response.data.tracking_number);
                            $('#form-add-parcel')[0].reset();
                            $('#selected-client-id').val('');
                            // Recharger la liste des colis
                            location.reload();
                        } else {
                            alert('❌ Erreur: ' + (response.data ? response.data.message : 'Erreur inconnue'));
                        }
                    },
                    error: function() {
                        alert('❌ Erreur de connexion');
                    },
                    complete: function() {
                        $('#form-add-parcel button[type="submit"]').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Créer le Colis');
                    }
                });
            });

            // Soumission formulaire ajouter client
            $('#form-add-client').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                formData += '&action=colis224_client_create_client&nonce=<?php echo wp_create_nonce('colis224_client_portal'); ?>';

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        $('#form-add-client button[type="submit"]').prop('disabled', true).text('Création...');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Client créé avec succès !');
                            $('#form-add-client')[0].reset();
                        } else {
                            alert('❌ Erreur: ' + (response.data ? response.data.message : 'Erreur inconnue'));
                        }
                    },
                    error: function() {
                        alert('❌ Erreur de connexion');
                    },
                    complete: function() {
                        $('#form-add-client button[type="submit"]').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Créer le Client');
                    }
                });
            });

            // Soumission formulaire ajouter départ
            $('#form-add-departure').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                formData += '&action=colis224_client_create_departure&nonce=<?php echo wp_create_nonce('colis224_client_portal'); ?>';

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        $('#form-add-departure button[type="submit"]').prop('disabled', true).text('Création...');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Départ créé avec succès !');
                            $('#form-add-departure')[0].reset();
                        } else {
                            alert('❌ Erreur: ' + (response.data ? response.data.message : 'Erreur inconnue'));
                        }
                    },
                    error: function() {
                        alert('❌ Erreur de connexion');
                    },
                    complete: function() {
                        $('#form-add-departure button[type="submit"]').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Créer le Départ');
                    }
                });
            });

            // Toggle Chat
            $('#btn-toggle-chat').on('click', function() {
                console.log('🔔 Bouton chat cliqué');
                $('#live-chat-widget').slideToggle();
                loadChatMessages();
            });

            $('#btn-close-chat').on('click', function() {
                $('#live-chat-widget').slideUp();
            });

            // Nouveau ticket
            $('#btn-new-ticket').on('click', function() {
                $('#new-ticket-form').slideDown();
            });

            $('#btn-cancel-ticket').on('click', function() {
                $('#new-ticket-form').slideUp();
                $('#form-create-ticket')[0].reset();
            });

            // Créer un ticket
            $('#form-create-ticket').on('submit', function(e) {
                e.preventDefault();
                createTicket();
            });

            // Voir détails colis
            $('.btn-view-details').on('click', function() {
                var parcelId = $(this).data('parcel-id');
                showParcelDetails(parcelId);
            });

            // Voir ticket
            $('.btn-view-ticket').on('click', function() {
                var ticketId = $(this).data('ticket-id');
                showTicketConversation(ticketId);
            });

            // Fermer modals
            $('.btn-close-modal').on('click', function() {
                $(this).closest('.modal').fadeOut();
            });

            // Envoyer message chat
            $('#btn-send-message').on('click', function() {
                sendChatMessage();
            });

            $('#chat-message-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendChatMessage();
                }
            });

            // Créer un ticket
            function createTicket() {
                console.log('📝 Création du ticket...');
                var subject = $('#form-create-ticket input[name="subject"]').val();
                var message = $('#form-create-ticket textarea[name="message"]').val();
                
                console.log('Sujet:', subject);
                console.log('Message:', message);

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_client_create_ticket',
                        subject: subject,
                        message: message,
                        nonce: '<?php echo wp_create_nonce('colis224_client_portal'); ?>'
                    },
                    beforeSend: function() {
                        console.log('⏳ Envoi en cours...');
                    },
                    success: function(response) {
                        console.log('✅ Réponse reçue:', response);
                        if (response.success) {
                            alert('✅ Ticket créé avec succès ! Nous vous répondrons bientôt.');
                            location.reload();
                        } else {
                            alert('❌ Erreur : ' + (response.data ? response.data.message : 'Erreur inconnue'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Erreur AJAX:', status, error);
                        console.error('Réponse:', xhr.responseText);
                        alert('❌ Erreur de connexion. Veuillez réessayer.');
                    }
                });
            }

            function showParcelDetails(parcelId) {
                $('#parcel-details-modal').fadeIn();
                $('#parcel-details-content').html('<div class="loading">Chargement...</div>');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_client_get_parcel_details',
                        parcel_id: parcelId,
                        nonce: '<?php echo wp_create_nonce('colis224_client_portal'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#parcel-details-content').html(response.data.html);
                        } else {
                            var errorMsg = response.data && response.data.message ? response.data.message : 'Erreur inconnue';
                            $('#parcel-details-content').html('<div class="error-message" style="padding: 20px; text-align: center; color: #e74c3c;"><strong>❌ Erreur:</strong> ' + errorMsg + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error, xhr.responseText);
                        $('#parcel-details-content').html('<div class="error-message" style="padding: 20px; text-align: center; color: #e74c3c;"><strong>❌ Erreur de connexion</strong><br>Veuillez réessayer ou contacter le support.</div>');
                    }
                });
            }

            function showTicketConversation(ticketId) {
                $('#ticket-conversation-modal').fadeIn();
                $('#ticket-conversation-content').html('<div class="loading">Chargement...</div>');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_client_get_ticket_conversation',
                        ticket_id: ticketId,
                        nonce: '<?php echo wp_create_nonce('colis224_client_portal'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#ticket-conversation-content').html(response.data.html);

                            // Attacher le gestionnaire au bouton de réponse
                            $(document).on('click', '#btn-reply-ticket', function(e) {
                                e.preventDefault();
                                var ticketId = $(this).data('ticket-id');
                                var message = $('#ticket-reply-message').val().trim();

                                if (!message) {
                                    alert('❌ Veuillez écrire un message');
                                    return;
                                }

                                $.ajax({
                                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                    type: 'POST',
                                    data: {
                                        action: 'colis224_client_reply_ticket',
                                        ticket_id: ticketId,
                                        message: message,
                                        nonce: '<?php echo wp_create_nonce('colis224_client_portal'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            alert('✅ Réponse envoyée!');
                                            showTicketConversation(ticketId);
                                        } else {
                                            alert('❌ Erreur: ' + response.data.message);
                                        }
                                    },
                                    error: function() {
                                        alert('❌ Erreur de connexion');
                                    }
                                });
                            });
                        } else {
                            var errorMsg = response.data && response.data.message ? response.data.message : 'Erreur inconnue';
                            $('#ticket-conversation-content').html('<div class="error-message" style="padding: 20px; text-align: center; color: #e74c3c;"><strong>❌ Erreur:</strong> ' + errorMsg + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error, xhr.responseText);
                        $('#ticket-conversation-content').html('<div class="error-message" style="padding: 20px; text-align: center; color: #e74c3c;"><strong>❌ Erreur de connexion</strong><br>Veuillez réessayer ou contacter le support.</div>');
                    }
                });
            }

            function sendChatMessage() {
                var message = $('#chat-message-input').val().trim();
                if (!message) return;

                // Afficher le message immédiatement (échapper HTML sauf apostrophes)
                var escapedMessage = $('<div>').text(message).html();
                var messageHtml = '<div class="message message-client"><p>' + escapedMessage + '</p></div>';
                $('#chat-messages').append(messageHtml);
                $('#chat-message-input').val('');

                // Scroll en bas
                $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);

                // Envoyer via AJAX
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_client_send_message',
                        message: message,
                        nonce: '<?php echo wp_create_nonce('colis224_client_portal'); ?>'
                    }
                });
            }

            function loadChatMessages() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_client_get_messages',
                        nonce: '<?php echo wp_create_nonce('colis224_client_portal'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.messages) {
                            var html = '<div class="chat-welcome"><p>👋 Bonjour ! Comment pouvons-nous vous aider ?</p></div>';
                            response.data.messages.forEach(function(msg) {
                                var cssClass = msg.sender_type === 'client' ? 'message-client' : 'message-agent';
                                // Décoder les entités HTML pour les apostrophes
                                var decodedMessage = $('<textarea>').html(msg.message).text();
                                html += '<div class="message ' + cssClass + '"><p>' + $('<div>').text(decodedMessage).html() + '</p><span class="message-time">' + msg.created_at + '</span></div>';
                            });
                            $('#chat-messages').html(html);
                            $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                        }
                    }
                });
            }

            // Auto-refresh messages toutes les 30 secondes si le chat est ouvert
            setInterval(function() {
                if ($('#live-chat-widget').is(':visible')) {
                    loadChatMessages();
                }
            }, 30000);
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Afficher les prochains départs
     */
    private static function render_upcoming_departures() {
        // Charger la classe Departures si nécessaire
        if (!class_exists('Colis224_Departures')) {
            return '';
        }

        global $wpdb;

        // Récupérer les prochains départs (limités à 5)
        $table_departures = $wpdb->prefix . 'colis224_departures';
        $departures = $wpdb->get_results(
            "SELECT * FROM {$table_departures}
            WHERE departure_date >= CURDATE()
            AND status = 'scheduled'
            ORDER BY departure_date ASC
            LIMIT 5"
        );

        if (empty($departures)) {
            return '';
        }

        ob_start();
        ?>
        <div class="colis224-departures-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px; margin: 20px 0; color: white;">
            <h3 style="margin: 0 0 15px 0; color: white; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-airplane" style="font-size: 24px;"></span>
                ✈️ Prochains Départs
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                <?php foreach ($departures as $dep):
                    $transport_icon = $dep->transport_type === 'plane' ? '✈️' : '🚢';
                    $departure_datetime = new DateTime($dep->departure_date);
                    $formatted_date = $departure_datetime->format('d/m/Y');
                    $days_until = floor((strtotime($dep->departure_date) - time()) / (60 * 60 * 24));

                    $whatsapp_message = urlencode("Bonjour, je souhaite réserver pour le départ:\n📍 {$dep->departure_city} → {$dep->arrival_city}\n📅 {$formatted_date}\n{$transport_icon} Transport");
                    $whatsapp_url = "https://wa.me/{$dep->whatsapp_number}?text={$whatsapp_message}";
                ?>
                <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 10px; padding: 15px; border: 1px solid rgba(255,255,255,0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 28px;"><?php echo $transport_icon; ?></span>
                        <span style="background: rgba(255,255,255,0.3); padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                            <?php echo $days_until === 0 ? "Aujourd'hui" : ($days_until === 1 ? "Demain" : "Dans {$days_until} jours"); ?>
                        </span>
                    </div>

                    <div style="font-size: 14px; margin-bottom: 8px;">
                        📅 <strong><?php echo esc_html($formatted_date); ?></strong>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 16px;">
                        <strong><?php echo esc_html($dep->departure_city); ?></strong>
                        <span>→</span>
                        <strong><?php echo esc_html($dep->arrival_city); ?></strong>
                    </div>

                    <?php if ($dep->price_estimate && $dep->price_estimate > 0): ?>
                    <div style="margin-bottom: 10px; font-size: 14px; opacity: 0.9;">
                        💰 À partir de <strong><?php echo number_format($dep->price_estimate, 0, ',', ' ') . ' ' . esc_html($dep->currency); ?></strong>
                    </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank"
                       style="display: inline-block; background: #25D366; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-weight: bold; width: 100%; text-align: center; margin-top: 5px;">
                        📱 Réserver sur WhatsApp
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <p style="margin: 15px 0 0 0; font-size: 13px; opacity: 0.8; text-align: center;">
                💡 Cliquez sur "Réserver" pour contacter notre équipe via WhatsApp
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtenir les informations de fidélité du client
     */
    private static function get_client_loyalty_info($client_id) {
        global $wpdb;

        $total_parcels = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE client_id = %d",
            $client_id
        ));

        $total_spent = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(total_amount) FROM {$wpdb->prefix}colis224_parcels WHERE client_id = %d AND payment_status = 'Payé'",
            $client_id
        ));

        $points = intval($total_parcels) * 10;
        
        // Déterminer le niveau
        if ($points < 100) {
            $level = 'Bronze';
            $next_level = 'Argent';
            $next_threshold = 100;
        } elseif ($points < 500) {
            $level = 'Argent';
            $next_level = 'Or';
            $next_threshold = 500;
        } elseif ($points < 1000) {
            $level = 'Or';
            $next_level = 'Platine';
            $next_threshold = 1000;
        } else {
            $level = 'Platine';
            $next_level = null;
            $next_threshold = null;
        }

        $points_to_next = $next_threshold ? ($next_threshold - $points) : 0;
        $progress_percent = $next_threshold ? min(100, ($points / $next_threshold) * 100) : 100;

        return array(
            'level' => $level,
            'points' => $points,
            'total_parcels' => $total_parcels,
            'total_spent' => floatval($total_spent),
            'next_level' => $next_level,
            'points_to_next' => $points_to_next,
            'progress_percent' => $progress_percent
        );
    }

    /**
     * Obtenir les tickets du client
     */
    private static function get_client_tickets($client_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_support_tickets 
            WHERE client_id = %d 
            ORDER BY created_at DESC 
            LIMIT 10",
            $client_id
        ));
    }

    /**
     * Obtenir le nombre de messages non lus
     */
    private static function get_unread_messages_count($client_id) {
        global $wpdb;
        
        // D'abord, trouver les conversations du client
        $conversations = $wpdb->get_col($wpdb->prepare(
            "SELECT conversation_id FROM {$wpdb->prefix}colis224_chat_conversations 
            WHERE client_id = %d",
            $client_id
        ));
        
        if (empty($conversations)) {
            return 0;
        }
        
        // Ensuite compter les messages non lus dans ces conversations
        $placeholders = implode(',', array_fill(0, count($conversations), '%s'));
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_chat_messages 
            WHERE conversation_id IN ($placeholders) 
            AND sender_type = 'agent' 
            AND is_read = 0",
            $conversations
        ));
    }

    /**
     * Classe CSS pour le statut
     */
    private static function get_status_class($status) {
        $status = strtolower(str_replace(' ', '-', $status));
        return $status;
    }

    /**
     * AJAX: Créer un ticket côté client
     */
    public function ajax_create_ticket() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        // Vérifier les données POST
        if (empty($_POST['subject']) || empty($_POST['message'])) {
            wp_send_json_error(array('message' => 'Sujet et message requis'));
            return;
        }

        // $client_id déjà récupéré plus haut via get_current_client_id()
        $subject = sanitize_text_field(wp_unslash($_POST['subject']));
        $message = sanitize_textarea_field(wp_unslash($_POST['message']));

        global $wpdb;
        
        // Générer un numéro de ticket unique
        $ticket_number = 'TKT-' . date('YmdHis') . '-' . wp_generate_password(4, false);
        
        // Créer le ticket dans la BONNE table
        $result = $wpdb->insert(
            $wpdb->prefix . 'colis224_support_tickets',
            array(
                'ticket_number' => $ticket_number,
                'client_id' => $client_id,
                'subject' => $subject,
                'category' => 'autre',
                'priority' => 'normale',
                'status' => 'ouvert',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        if (!$result) {
            wp_send_json_error(array(
                'message' => 'Erreur lors de la création du ticket: ' . $wpdb->last_error
            ));
            return;
        }

        $ticket_id = $wpdb->insert_id;

        // Ajouter le premier message dans la BONNE table
        $msg_result = $wpdb->insert(
            $wpdb->prefix . 'colis224_support_messages',
            array(
                'ticket_id' => $ticket_id,
                'user_type' => 'client',
                'user_id' => $client_id,
                'message' => $message,
                'is_internal' => 0,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%d', '%s')
        );

        if ($msg_result) {
            // Créer une notification pour l'admin
            $admin_id = 1; // Admin par défaut
            $wpdb->insert(
                $wpdb->prefix . 'colis224_notifications',
                array(
                    'recipient_type' => 'team',
                    'recipient_id' => $admin_id,
                    'notification_type' => 'email',
                    'subject' => 'Nouveau ticket créé',
                    'message' => 'Client a créé un ticket: "' . $subject . '"',
                    'status' => 'pending'
                ),
                array('%s', '%d', '%s', '%s', '%s', '%s')
            );

            // Envoyer un email à l'admin
            $admin = get_user_by('ID', $admin_id);
            if ($admin) {
                wp_mail(
                    $admin->user_email,
                    '🎫 Nouveau ticket créé: ' . $ticket_number,
                    'Sujet: ' . $subject . "\n" .
                    'Message: ' . substr($message, 0, 200) . "\n\n" .
                    'Consultez: ' . admin_url('admin.php?page=colis224-support')
                );
            }

            wp_send_json_success(array(
                'message' => 'Ticket créé avec succès',
                'ticket_id' => $ticket_id,
                'ticket_number' => $ticket_number
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Ticket créé mais erreur avec le message'
            ));
        }
    }

    /**
     * AJAX: Obtenir la conversation d'un ticket
     */
    public function ajax_get_ticket_conversation() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        $ticket_id = intval($_POST['ticket_id']);
        // $client_id déjà récupéré via verify_client_ajax_auth()
        global $wpdb;

        // Vérifier que le ticket appartient au client
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_support_tickets 
            WHERE id = %d AND client_id = %d",
            $ticket_id,
            $client_id
        ));

        if (!$ticket) {
            wp_send_json_error(array('message' => 'Ticket introuvable'));
            return;
        }

        // Récupérer les messages du ticket
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_support_messages 
            WHERE ticket_id = %d 
            ORDER BY created_at ASC",
            $ticket_id
        ));

        // Générer le HTML de la conversation
        ob_start();
        ?>
        <div class="ticket-conversation">
            <div class="ticket-info">
                <strong>Ticket #{<?php echo $ticket->ticket_number; ?></strong>
                <span class="ticket-status"><?php echo ucfirst($ticket->status); ?></span>
            </div>

            <div class="conversation-messages">
                <?php foreach ($messages as $msg): ?>
                <div class="message message-<?php echo $msg->user_type; ?>">
                    <div class="message-header">
                        <strong><?php echo $msg->user_type === 'client' ? 'Vous' : 'Support'; ?></strong>
                        <small><?php echo date('d/m/Y H:i', strtotime($msg->created_at)); ?></small>
                    </div>
                    <div class="message-body">
                        <?php echo nl2br(esc_html($msg->message)); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ticket-reply-form">
                <div class="ticket-reply-header">
                    <h4>📝 Répondre au support</h4>
                    <button class="btn-close-modal">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <textarea id="ticket-reply-message" placeholder="Écrivez votre réponse ici..." rows="8" style="min-height: 200px; width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                <button id="btn-reply-ticket" class="btn-primary" data-ticket-id="<?php echo $ticket_id; ?>" style="width: 100%; margin-top: 10px; padding: 12px;">
                    ✉️ Envoyer la réponse
                </button>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Envoyer une réponse à un ticket
     */
    public function ajax_reply_ticket() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        $ticket_id = intval($_POST['ticket_id']);
        $message = sanitize_textarea_field(wp_unslash($_POST['message']));
        // $client_id déjà récupéré via verify_client_ajax_auth()

        if (empty($message)) {
            wp_send_json_error(array('message' => 'Message vide'));
            return;
        }

        global $wpdb;

        // Vérifier que le ticket appartient au client
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_support_tickets 
            WHERE id = %d AND client_id = %d",
            $ticket_id,
            $client_id
        ));

        if (!$ticket) {
            wp_send_json_error(array('message' => 'Ticket introuvable'));
            return;
        }

        // Insérer le message de réponse
        $result = $wpdb->insert(
            $wpdb->prefix . 'colis224_support_messages',
            array(
                'ticket_id' => $ticket_id,
                'user_type' => 'client',
                'user_id' => $client_id,
                'message' => $message,
                'is_internal' => 0
            ),
            array('%d', '%s', '%d', '%s', '%d')
        );

        if ($result) {
            // Mettre à jour le ticket
            $wpdb->update(
                $wpdb->prefix . 'colis224_support_tickets',
                array('updated_at' => current_time('mysql')),
                array('id' => $ticket_id)
            );

            // Notifier l'admin
            $admin_id = 1;
            $wpdb->insert(
                $wpdb->prefix . 'colis224_notifications',
                array(
                    'recipient_type' => 'team',
                    'recipient_id' => $admin_id,
                    'notification_type' => 'email',
                    'subject' => 'Réponse au ticket ' . $ticket->ticket_number,
                    'message' => 'Le client a répondu au ticket: "' . substr($message, 0, 100) . '..."',
                    'status' => 'pending'
                ),
                array('%s', '%d', '%s', '%s', '%s', '%s')
            );

            wp_send_json_success(array('message' => 'Réponse envoyée'));
        } else {
            wp_send_json_error(array('message' => 'Erreur: ' . $wpdb->last_error));
        }
    }

    /**
     * AJAX: Obtenir les détails d'un colis
     */
    public function ajax_get_parcel_details() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        $parcel_id = intval($_POST['parcel_id']);
        global $wpdb;

        // $client_id déjà récupéré via verify_client_ajax_auth()
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_parcels WHERE id = %d AND client_id = %d",
            $parcel_id,
            $client_id
        ));

        if (!$parcel) {
            wp_send_json_error(array('message' => 'Colis introuvable'));
            return;
        }

        // Générer HTML des détails
        ob_start();
        ?>
        <div class="parcel-details">
            <div class="detail-row">
                <strong>📍 Numéro de suivi:</strong>
                <span><?php echo esc_html($parcel->tracking_number); ?></span>
            </div>
            <div class="detail-row">
                <strong>📦 Statut:</strong>
                <span class="status-badge status-<?php echo esc_attr(self::get_status_class($parcel->status)); ?>">
                    <?php echo esc_html($parcel->status); ?>
                </span>
            </div>
            <div class="detail-row">
                <strong>👤 Destinataire:</strong>
                <span><?php echo esc_html($parcel->recipient_name); ?></span>
            </div>
            <div class="detail-row">
                <strong>📞 Téléphone:</strong>
                <span><?php echo esc_html($parcel->recipient_phone); ?></span>
            </div>
            <div class="detail-row">
                <strong>📍 Adresse:</strong>
                <span><?php echo esc_html($parcel->recipient_address); ?></span>
            </div>
            <div class="detail-row">
                <strong>⚖️ Poids:</strong>
                <span><?php echo number_format($parcel->weight, 2); ?> kg</span>
            </div>
            <div class="detail-row">
                <strong>💰 Montant:</strong>
                <span><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo esc_html($parcel->currency); ?></span>
            </div>
            <div class="detail-row">
                <strong>💳 Statut paiement:</strong>
                <span><?php echo esc_html($parcel->payment_status); ?></span>
            </div>
            <?php
            // Les notes sont masquées pour les clients (notes internes uniquement visibles dans l'admin)
            // Ne pas afficher les notes côté client pour des raisons de confidentialité
            ?>
            <div class="detail-row">
                <strong>📅 Date de création:</strong>
                <span><?php echo date('d/m/Y H:i', strtotime($parcel->created_at)); ?></span>
            </div>

            <?php
            // Affichage des photos du colis
            if (!empty($parcel->photos)) {
                $photos = json_decode($parcel->photos, true);
                if (is_array($photos) && count($photos) > 0) {
                    ?>
                    <div class="detail-section">
                        <h4>📸 Photos du colis</h4>
                        <div class="parcel-photos-grid">
                            <?php foreach ($photos as $photo_url): ?>
                                <?php if (!empty($photo_url)): ?>
                                    <div class="photo-item">
                                        <a href="<?php echo esc_url($photo_url); ?>" target="_blank" rel="noopener">
                                            <img src="<?php echo esc_url($photo_url); ?>" alt="Photo du colis" loading="lazy">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                }
            }

            // Affichage du reçu
            if (!empty($parcel->receipt_photo)) {
                ?>
                <div class="detail-section">
                    <h4>🧾 Reçu de paiement</h4>
                    <div class="receipt-photo">
                        <a href="<?php echo esc_url($parcel->receipt_photo); ?>" target="_blank" rel="noopener">
                            <img src="<?php echo esc_url($parcel->receipt_photo); ?>" alt="Reçu de paiement" loading="lazy">
                        </a>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <style>
        .parcel-details {
            padding: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .detail-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        .detail-section h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
        }
        .parcel-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .photo-item {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .photo-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .photo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }
        .receipt-photo {
            margin-top: 15px;
            max-width: 400px;
        }
        .receipt-photo img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .receipt-photo:hover img {
            transform: scale(1.02);
        }
        @media (max-width: 768px) {
            .parcel-photos-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 10px;
            }
            .photo-item img {
                height: 100px;
            }
            .receipt-photo {
                max-width: 100%;
            }
        }
        </style>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Envoyer un message chat
     */
    public function ajax_send_message() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        // $client_id déjà récupéré via verify_client_ajax_auth()
        $message = sanitize_textarea_field(wp_unslash($_POST['message']));

        if (empty($message)) {
            wp_send_json_error(array('message' => 'Message vide'));
            return;
        }

        global $wpdb;

        // Générer un ID de conversation unique par client
        $conversation_id = 'CONV-' . $client_id;

        // Vérifier si la conversation existe
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}colis224_chat_conversations WHERE conversation_id = %s",
            $conversation_id
        ));

        // Si pas de conversation, la créer
        if (!$existing) {
            $client = $wpdb->get_row($wpdb->prepare(
                "SELECT name, email, phone FROM {$wpdb->prefix}colis224_clients WHERE id = %d",
                $client_id
            ));

            $wpdb->insert(
                $wpdb->prefix . 'colis224_chat_conversations',
                array(
                    'conversation_id' => $conversation_id,
                    'client_id' => $client_id,
                    'client_name' => $client->name,
                    'client_email' => $client->email,
                    'client_phone' => $client->phone,
                    'status' => 'active'
                ),
                array('%s', '%d', '%s', '%s', '%s', '%s')
            );
        }

        // Insérer le message
        $result = $wpdb->insert(
            $wpdb->prefix . 'colis224_chat_messages',
            array(
                'conversation_id' => $conversation_id,
                'sender_type' => 'client',
                'sender_id' => $client_id,
                'sender_name' => 'Client',
                'message' => $message,
                'is_read' => 0
            ),
            array('%s', '%s', '%d', '%s', '%s', '%d')
        );

        if ($result) {
            // Créer une notification pour l'admin
            $admin_id = 1; // Admin par défaut
            $wpdb->insert(
                $wpdb->prefix . 'colis224_notifications',
                array(
                    'recipient_type' => 'team',
                    'recipient_id' => $admin_id,
                    'notification_type' => 'email',
                    'subject' => 'Nouveau message du client',
                    'message' => 'Un client a envoyé un message: "' . substr($message, 0, 100) . '..."',
                    'status' => 'pending'
                ),
                array('%s', '%d', '%s', '%s', '%s', '%s')
            );

            // Envoyer un email à l'admin
            $admin = get_user_by('ID', $admin_id);
            if ($admin) {
                wp_mail(
                    $admin->user_email,
                    '💬 Nouveau message chat du client',
                    'Client ID: ' . $client_id . "\n" .
                    'Message: ' . $message . "\n\n" .
                    'Consultez: ' . admin_url('admin.php?page=colis224-live-chat')
                );
            }

            wp_send_json_success(array('message' => 'Message envoyé'));
        } else {
            wp_send_json_error(array('message' => 'Erreur: ' . $wpdb->last_error));
        }
    }

    /**
     * AJAX: Obtenir les messages chat
     */
    public function ajax_get_messages() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        // $client_id déjà récupéré via verify_client_ajax_auth()
        $conversation_id = 'CONV-' . $client_id;
        global $wpdb;

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_chat_messages 
            WHERE conversation_id = %s 
            ORDER BY created_at ASC 
            LIMIT 50",
            $conversation_id
        ));

        // Marquer les messages de l'agent comme lus
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}colis224_chat_messages 
            SET is_read = 1 
            WHERE conversation_id = %s 
            AND sender_type = 'agent' 
            AND is_read = 0",
            $conversation_id
        ));

        wp_send_json_success(array('messages' => $messages));
    }

    /**
     * AJAX: Obtenir le nombre de messages non lus
     */
    public function ajax_get_unread_count() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('count' => 0));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_success(array('count' => 0));
            return;
        }

        // $client_id déjà récupéré via verify_client_ajax_auth()
        $conversation_id = 'CONV-' . $client_id;
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_chat_messages 
            WHERE conversation_id = %s 
            AND sender_type = 'agent' 
            AND is_read = 0",
            $conversation_id
        ));

        wp_send_json_success(array('count' => $count ?: 0));
    }

    /**
     * AJAX: Créer un colis depuis l'espace client
     */
    public function ajax_create_parcel() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        // SÉCURITÉ: Vérifier les permissions (réservé aux admins, agents, éditeurs, auteurs)
        if (!current_user_can('colis224_create_parcel') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '🚫 Permission refusée. Cette action est réservée aux administrateurs et agents.'));
            return;
        }

        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        // $client_id déjà récupéré via verify_client_ajax_auth()

        // Générer le numéro de suivi
        $recipient_phone = sanitize_text_field($_POST['recipient_phone']);
        $last4 = substr(preg_replace('/\s/', '', $recipient_phone), -4);
        $tracking_number = 'PA' . $last4;
        
        // Vérifier l'unicité
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
            $tracking_number
        ));
        
        if ($count > 0) {
            $tracking_number = 'PA' . $last4 . '-' . substr(time(), -3) . rand(10, 99);
        }

        $data = array(
            'tracking_number' => $tracking_number,
            'client_id' => !empty($_POST['client_id']) ? intval($_POST['client_id']) : $client_id,
            'recipient_name' => sanitize_text_field($_POST['recipient_name']),
            'recipient_phone' => $recipient_phone,
            'recipient_address' => sanitize_textarea_field($_POST['recipient_address']),
            'weight' => floatval($_POST['weight']),
            'total_amount' => floatval($_POST['total_amount']),
            'currency' => 'GNF',
            'status' => 'En attente',
            'payment_status' => 'Non payé',
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert($table_parcels, $data);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Colis créé avec succès',
                'tracking_number' => $tracking_number
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Erreur lors de la création: ' . $wpdb->last_error
            ));
        }
    }

    /**
     * AJAX: Créer un client depuis l'espace client
     */
    public function ajax_create_client() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        // SÉCURITÉ: Vérifier les permissions (réservé aux admins, agents, éditeurs, auteurs)
        if (!current_user_can('colis224_manage_clients') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '🚫 Permission refusée. Cette action est réservée aux administrateurs et agents.'));
            return;
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Vérifier si le téléphone existe déjà
        $phone = sanitize_text_field($_POST['phone']);
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_clients WHERE phone = %s",
            $phone
        ));

        if ($exists > 0) {
            wp_send_json_error(array('message' => 'Un client avec ce numéro de téléphone existe déjà'));
            return;
        }

        $data = array(
            'type' => sanitize_text_field($_POST['type']),
            'name' => sanitize_text_field($_POST['name']),
            'company_name' => sanitize_text_field($_POST['company_name'] ?? ''),
            'phone' => $phone,
            'email' => sanitize_email($_POST['email'] ?? ''),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'discount_rate' => 0,
            'balance' => 0,
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert($table_clients, $data);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Client créé avec succès',
                'client_id' => $wpdb->insert_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Erreur lors de la création: ' . $wpdb->last_error
            ));
        }
    }

    /**
     * AJAX: Créer un départ depuis l'espace client
     */
    public function ajax_create_departure() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        // SÉCURITÉ: Vérifier les permissions (réservé aux admins, agents, éditeurs, auteurs)
        if (!current_user_can('colis224_create_parcel') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '🚫 Permission refusée. Cette action est réservée aux administrateurs et agents.'));
            return;
        }

        if (!class_exists('Colis224_Departures')) {
            wp_send_json_error(array('message' => 'Classe Colis224_Departures introuvable'));
            return;
        }

        $countries = Colis224_Departures::get_countries();
        $departure_country_code = sanitize_text_field($_POST['departure_country_code']);
        $arrival_country_code = sanitize_text_field($_POST['arrival_country_code']);

        $data = array(
            'departure_city' => sanitize_text_field($_POST['departure_city']),
            'departure_country' => $countries[$departure_country_code]['name'] ?? '',
            'departure_country_code' => $departure_country_code,
            'arrival_city' => sanitize_text_field($_POST['arrival_city']),
            'arrival_country' => $countries[$arrival_country_code]['name'] ?? '',
            'arrival_country_code' => $arrival_country_code,
            'departure_date' => sanitize_text_field($_POST['departure_date']),
            'departure_time' => sanitize_text_field($_POST['departure_time'] ?? '08:00:00'),
            'transport_type' => sanitize_text_field($_POST['transport_type']),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'status' => 'scheduled',
            'is_active' => 1,
            'created_by' => 0 // Client créé depuis l'espace client
        );

        $departure_id = Colis224_Departures::add_departure($data);

        if ($departure_id) {
            wp_send_json_success(array(
                'message' => 'Départ créé avec succès',
                'departure_id' => $departure_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la création du départ'));
        }
    }

    /**
     * AJAX: Rechercher des clients existants
     */
    public function ajax_search_clients() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_client_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier l'authentification WordPress
        $client_id = $this->verify_client_ajax_auth();
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Non connecté. Veuillez vous reconnecter.'));
            return;
        }

        // SÉCURITÉ: Vérifier les permissions (réservé aux admins, agents, éditeurs, auteurs)
        if (!current_user_can('colis224_manage_clients') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '🚫 Permission refusée. Cette action est réservée aux administrateurs et agents.'));
            return;
        }

        $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';

        if (empty($term) || strlen($term) < 2) {
            wp_send_json_success(array());
            return;
        }

        // v2.18.23: Cache des résultats de recherche pour performances maximales
        $cache_key = 'colis224_client_search_' . md5($term);
        $cached_results = get_transient($cache_key);

        if ($cached_results !== false) {
            wp_send_json_success($cached_results);
            return;
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // v2.18.24: Requête SQL ultra-optimisée pour recherche instantanée
        // Priorité : match exact > commence par > contient
        $results = $wpdb->get_results($wpdb->prepare("
            (
                SELECT id, name, phone, email, company_name, 1 as priority
                FROM $table_clients
                WHERE name = %s OR phone = %s
                LIMIT 3
            )
            UNION
            (
                SELECT id, name, phone, email, company_name, 2 as priority
                FROM $table_clients
                WHERE (name LIKE %s OR phone LIKE %s)
                  AND name != %s AND phone != %s
                LIMIT 3
            )
            UNION
            (
                SELECT id, name, phone, email, company_name, 3 as priority
                FROM $table_clients
                WHERE (name LIKE %s OR email LIKE %s OR company_name LIKE %s)
                  AND name NOT LIKE %s AND phone NOT LIKE %s
                LIMIT 2
            )
            ORDER BY priority ASC, name ASC
            LIMIT 5
        ",
            // Match exact
            $term,
            $term,
            // Commence par
            $wpdb->esc_like($term) . '%',
            $wpdb->esc_like($term) . '%',
            $term,
            $term,
            // Contient
            '%' . $wpdb->esc_like($term) . '%',
            '%' . $wpdb->esc_like($term) . '%',
            '%' . $wpdb->esc_like($term) . '%',
            $wpdb->esc_like($term) . '%',
            $wpdb->esc_like($term) . '%'
        ));

        $formatted_results = array();
        foreach ($results as $client) {
            $formatted_results[] = array(
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'company_name' => $client->company_name
            );
        }

        // v2.18.23: Sauvegarder dans le cache pour 5 minutes
        set_transient($cache_key, $formatted_results, 5 * MINUTE_IN_SECONDS);

        wp_send_json_success($formatted_results);
    }
}

// Initialiser la classe
new Colis224_Client_Portal_Enhanced();
