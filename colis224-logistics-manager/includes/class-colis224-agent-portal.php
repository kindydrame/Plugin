<?php
/**
 * Portail Agent - Interface séparée pour les agents
 * Version: 2.18.0
 *
 * Ce fichier gère UNIQUEMENT l'espace agent :
 * - Création de colis (soumis à validation)
 * - Création de départs (soumis à validation)
 * - Visualisation des actions en attente
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Agent_Portal {

    public function __construct() {
        // Enregistrer le shortcode
        add_shortcode('colis224_agent_portal', array($this, 'render_agent_portal'));

        // AJAX handlers pour les agents
        add_action('wp_ajax_colis224_agent_create_parcel', array($this, 'ajax_create_parcel'));
        add_action('wp_ajax_colis224_agent_create_departure', array($this, 'ajax_create_departure'));
        add_action('wp_ajax_colis224_agent_get_pending', array($this, 'ajax_get_pending_items'));
    }

    /**
     * Rendu du portail agent [colis224_agent_portal]
     */
    public function render_agent_portal($atts) {
        ob_start();

        // SÉCURITÉ: Vérifier que l'utilisateur est connecté à WordPress
        if (!is_user_logged_in()) {
            $this->display_login_required();
            return ob_get_clean();
        }

        // SÉCURITÉ: Vérifier les permissions agent
        if (!$this->is_agent()) {
            $this->display_access_denied();
            return ob_get_clean();
        }

        // Afficher le dashboard agent
        $this->display_agent_dashboard();

        return ob_get_clean();
    }

    /**
     * Vérifier si l'utilisateur a les permissions agent
     */
    private function is_agent() {
        // Admins ont toujours accès
        if (current_user_can('administrator') || current_user_can('manage_options')) {
            return true;
        }

        // Vérifier les capabilities Colis224 pour agents
        $agent_capabilities = array(
            'colis224_manage_all',      // Manager/Admin Colis224
            'colis224_create_parcel',   // Agent qui peut créer des colis
            'colis224_view_parcels',    // Agent qui peut voir des colis
        );

        foreach ($agent_capabilities as $cap) {
            if (current_user_can($cap)) {
                return true;
            }
        }

        // Vérifier si l'utilisateur a un rôle contenant "agent" (insensible à la casse)
        $user = wp_get_current_user();
        if (!empty($user->roles)) {
            foreach ($user->roles as $role) {
                if (stripos($role, 'agent') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Message si non connecté
     */
    private function display_login_required() {
        ?>
        <div class="colis224-agent-portal colis224-error">
            <div class="colis224-notice colis224-notice-error">
                <h3>🔒 Authentification Requise</h3>
                <p>Vous devez être connecté à WordPress pour accéder à l'espace agent.</p>
                <p><a href="<?php echo wp_login_url(get_permalink()); ?>" class="colis224-btn colis224-btn-primary">
                    Se connecter
                </a></p>
            </div>
        </div>
        <?php
    }

    /**
     * Message si permissions insuffisantes
     */
    private function display_access_denied() {
        $user = wp_get_current_user();
        ?>
        <div class="colis224-agent-portal colis224-error">
            <div class="colis224-notice colis224-notice-error">
                <h3>⛔ Accès Refusé</h3>
                <p>Vous êtes connecté en tant que <strong><?php echo esc_html($user->display_name); ?></strong>
                   (rôle: <?php echo implode(', ', $user->roles); ?>)</p>
                <p>Seuls les administrateurs et agents peuvent accéder à cet espace.</p>
                <p>Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Dashboard principal de l'agent
     */
    private function display_agent_dashboard() {
        global $wpdb;
        $user = wp_get_current_user();

        // Récupérer les statistiques de l'agent
        $pending_parcels = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}colis224_parcels
            WHERE approval_status = 'pending'
            AND created_by = {$user->ID}
        ");

        $pending_departures = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}colis224_departures
            WHERE approval_status = 'pending'
            AND created_by = {$user->ID}
        ");

        ?>
        <div class="colis224-agent-portal" id="colis224-agent-portal">

            <!-- Header Agent -->
            <div class="colis224-agent-header">
                <div class="agent-welcome">
                    <h2>👋 Espace Agent - <?php echo esc_html($user->display_name); ?></h2>
                    <p class="agent-role">
                        <span class="badge badge-agent">🔑 Agent WordPress</span>
                        <span class="agent-email"><?php echo esc_html($user->user_email); ?></span>
                    </p>
                </div>

                <div class="agent-stats">
                    <div class="stat-box stat-pending">
                        <span class="stat-value"><?php echo $pending_parcels; ?></span>
                        <span class="stat-label">Colis en attente</span>
                    </div>
                    <div class="stat-box stat-departures">
                        <span class="stat-value"><?php echo $pending_departures; ?></span>
                        <span class="stat-label">Départs en attente</span>
                    </div>
                </div>
            </div>

            <!-- Notice importante -->
            <div class="colis224-notice colis224-notice-info">
                <p><strong>ℹ️ Important:</strong> Toutes vos actions (création de colis/départs) sont soumises à validation par l'administrateur avant d'être définitives.</p>
            </div>

            <!-- Onglets -->
            <div class="colis224-tabs">
                <button class="tab-btn active" data-tab="create-parcel">
                    <span class="dashicons dashicons-plus"></span> Créer un Colis
                </button>
                <button class="tab-btn" data-tab="create-departure">
                    <span class="dashicons dashicons-airplane"></span> Créer un Départ
                </button>
                <button class="tab-btn" data-tab="pending">
                    <span class="dashicons dashicons-clock"></span> En Attente (<?php echo ($pending_parcels + $pending_departures); ?>)
                </button>
            </div>

            <!-- Contenu des onglets -->
            <div class="colis224-tabs-content">

                <!-- Onglet: Créer un Colis -->
                <div class="tab-content active" id="tab-create-parcel">
                    <?php $this->render_create_parcel_form(); ?>
                </div>

                <!-- Onglet: Créer un Départ -->
                <div class="tab-content" id="tab-create-departure">
                    <?php $this->render_create_departure_form(); ?>
                </div>

                <!-- Onglet: Actions en Attente -->
                <div class="tab-content" id="tab-pending">
                    <?php $this->render_pending_items(); ?>
                </div>

            </div>

            <!-- Déconnexion -->
            <div class="agent-footer">
                <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="btn-logout">
                    <span class="dashicons dashicons-exit"></span> Déconnexion
                </a>
            </div>

        </div>

        <!-- JavaScript pour les onglets et AJAX -->
        <script>
        jQuery(document).ready(function($) {
            // Gestion des onglets
            $('.tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $('#tab-' + tab).addClass('active');
            });

            // Soumission création colis
            $('#form-agent-create-parcel').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                formData += '&action=colis224_agent_create_parcel';
                formData += '&nonce=<?php echo wp_create_nonce('colis224_agent_portal'); ?>';

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data.message);
                        $('#form-agent-create-parcel')[0].reset();
                        // Recharger le compteur
                        location.reload();
                    } else {
                        alert('❌ ' + response.data.message);
                    }
                });
            });

            // Soumission création départ
            $('#form-agent-create-departure').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                formData += '&action=colis224_agent_create_departure';
                formData += '&nonce=<?php echo wp_create_nonce('colis224_agent_portal'); ?>';

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data.message);
                        $('#form-agent-create-departure')[0].reset();
                        location.reload();
                    } else {
                        alert('❌ ' + response.data.message);
                    }
                });
            });
        });
        </script>

        <style>
        .colis224-agent-portal {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .colis224-agent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        .badge-agent {
            background: #0073aa;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: bold;
        }

        .agent-stats {
            display: flex;
            gap: 15px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            min-width: 120px;
        }

        .stat-value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }

        .stat-label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }

        .colis224-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-btn {
            padding: 12px 20px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #0073aa;
        }

        .tab-btn.active {
            color: #0073aa;
            border-bottom-color: #0073aa;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .agent-form {
            max-width: 800px;
        }

        .agent-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .agent-form .form-group {
            margin-bottom: 15px;
        }

        .agent-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #374151;
        }

        .agent-form input,
        .agent-form select,
        .agent-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .agent-form textarea {
            min-height: 80px;
        }

        .colis224-btn {
            padding: 12px 24px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .colis224-btn:hover {
            background: #005a87;
            transform: translateY(-2px);
        }

        .pending-item {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #ffc107;
        }

        .pending-item h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }

        .agent-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        </style>
        <?php
    }

    /**
     * Formulaire de création de colis
     */
    private function render_create_parcel_form() {
        global $wpdb;

        // Récupérer les données nécessaires
        $clients = $wpdb->get_results("SELECT id, name, phone FROM {$wpdb->prefix}colis224_clients ORDER BY name");
        $countries = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_countries WHERE is_active = 1 ORDER BY name");
        $transport_modes = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_transport_modes ORDER BY name");

        ?>
        <div class="agent-form">
            <h3>📦 Nouveau Colis (Soumission à Validation)</h3>

            <form id="form-agent-create-parcel" class="colis224-form">

                <div class="form-row">
                    <div class="form-group">
                        <label for="client_id">Client *</label>
                        <select name="client_id" id="client_id" required>
                            <option value="">Sélectionner un client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->id; ?>">
                                    <?php echo esc_html($client->name); ?> - <?php echo esc_html($client->phone); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tracking_number">Numéro de Suivi *</label>
                        <input type="text" name="tracking_number" id="tracking_number"
                               placeholder="Ex: PA1234" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sender_name">Expéditeur</label>
                        <input type="text" name="sender_name" id="sender_name">
                    </div>

                    <div class="form-group">
                        <label for="sender_phone">Téléphone Expéditeur</label>
                        <input type="text" name="sender_phone" id="sender_phone">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="recipient_name">Destinataire *</label>
                        <input type="text" name="recipient_name" id="recipient_name" required>
                    </div>

                    <div class="form-group">
                        <label for="recipient_phone">Téléphone Destinataire *</label>
                        <input type="text" name="recipient_phone" id="recipient_phone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="recipient_address">Adresse Destinataire *</label>
                    <textarea name="recipient_address" id="recipient_address" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="origin_country_id">Pays d'Origine</label>
                        <select name="origin_country_id" id="origin_country_id">
                            <option value="">Sélectionner</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country->id; ?>">
                                    <?php echo esc_html($country->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="destination_country_id">Pays de Destination</label>
                        <select name="destination_country_id" id="destination_country_id">
                            <option value="">Sélectionner</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country->id; ?>">
                                    <?php echo esc_html($country->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="transport_mode_id">Mode de Transport</label>
                        <select name="transport_mode_id" id="transport_mode_id">
                            <option value="">Sélectionner</option>
                            <?php foreach ($transport_modes as $mode): ?>
                                <option value="<?php echo $mode->id; ?>">
                                    <?php echo esc_html($mode->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="weight">Poids (kg) *</label>
                        <input type="number" step="0.01" name="weight" id="weight" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes"></textarea>
                </div>

                <button type="submit" class="colis224-btn colis224-btn-primary">
                    <span class="dashicons dashicons-yes"></span> Soumettre pour Validation
                </button>
            </form>
        </div>
        <?php
    }

    /**
     * Formulaire de création de départ
     */
    private function render_create_departure_form() {
        ?>
        <div class="agent-form">
            <h3>✈️ Nouveau Départ (Soumission à Validation)</h3>

            <form id="form-agent-create-departure" class="colis224-form">

                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_number">Numéro de Départ *</label>
                        <input type="text" name="departure_number" id="departure_number"
                               placeholder="Ex: DEP2024001" required>
                    </div>

                    <div class="form-group">
                        <label for="transport_type">Type de Transport *</label>
                        <select name="transport_type" id="transport_type" required>
                            <option value="plane">✈️ Avion</option>
                            <option value="boat">🚢 Bateau</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_city">Ville de Départ *</label>
                        <input type="text" name="departure_city" id="departure_city" required>
                    </div>

                    <div class="form-group">
                        <label for="departure_country">Pays de Départ *</label>
                        <input type="text" name="departure_country" id="departure_country" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="arrival_city">Ville d'Arrivée *</label>
                        <input type="text" name="arrival_city" id="arrival_city" required>
                    </div>

                    <div class="form-group">
                        <label for="arrival_country">Pays d'Arrivée *</label>
                        <input type="text" name="arrival_country" id="arrival_country" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_date">Date de Départ *</label>
                        <input type="date" name="departure_date" id="departure_date" required>
                    </div>

                    <div class="form-group">
                        <label for="departure_time">Heure de Départ</label>
                        <input type="time" name="departure_time" id="departure_time">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes"></textarea>
                </div>

                <button type="submit" class="colis224-btn colis224-btn-primary">
                    <span class="dashicons dashicons-yes"></span> Soumettre pour Validation
                </button>
            </form>
        </div>
        <?php
    }

    /**
     * Liste des items en attente de validation
     */
    private function render_pending_items() {
        global $wpdb;
        $user = wp_get_current_user();

        $pending_parcels = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}colis224_parcels
            WHERE approval_status = 'pending'
            AND created_by = %d
            ORDER BY created_at DESC
        ", $user->ID));

        $pending_departures = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}colis224_departures
            WHERE approval_status = 'pending'
            AND created_by = %d
            ORDER BY created_at DESC
        ", $user->ID));

        ?>
        <div class="pending-items">
            <h3>⏳ Vos Actions en Attente de Validation</h3>

            <?php if (empty($pending_parcels) && empty($pending_departures)): ?>
                <p class="no-items">Aucune action en attente de validation.</p>
            <?php endif; ?>

            <?php if (!empty($pending_parcels)): ?>
                <h4>Colis en Attente (<?php echo count($pending_parcels); ?>)</h4>
                <?php foreach ($pending_parcels as $parcel): ?>
                    <div class="pending-item">
                        <h4>📦 Colis <?php echo esc_html($parcel->tracking_number); ?></h4>
                        <p><strong>Destinataire:</strong> <?php echo esc_html($parcel->recipient_name); ?></p>
                        <p><strong>Téléphone:</strong> <?php echo esc_html($parcel->recipient_phone); ?></p>
                        <p><strong>Poids:</strong> <?php echo esc_html($parcel->weight); ?> kg</p>
                        <p><strong>Soumis le:</strong> <?php echo date('d/m/Y H:i', strtotime($parcel->created_at)); ?></p>
                        <p><small>En attente de validation par l'administrateur</small></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($pending_departures)): ?>
                <h4>Départs en Attente (<?php echo count($pending_departures); ?>)</h4>
                <?php foreach ($pending_departures as $departure): ?>
                    <div class="pending-item">
                        <h4>✈️ Départ <?php echo esc_html($departure->departure_number); ?></h4>
                        <p><strong>Route:</strong> <?php echo esc_html($departure->departure_city); ?> → <?php echo esc_html($departure->arrival_city); ?></p>
                        <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($departure->departure_date)); ?></p>
                        <p><strong>Soumis le:</strong> <?php echo date('d/m/Y H:i', strtotime($departure->created_at)); ?></p>
                        <p><small>En attente de validation par l'administrateur</small></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX: Créer un colis (soumis à validation)
     */
    public function ajax_create_parcel() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_agent_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier les permissions
        if (!$this->is_agent()) {
            wp_send_json_error(array('message' => '⛔ Accès refusé'));
            return;
        }

        global $wpdb;
        $user = wp_get_current_user();

        // Récupérer les données
        $data = array(
            'tracking_number' => sanitize_text_field($_POST['tracking_number']),
            'client_id' => intval($_POST['client_id']),
            'sender_name' => sanitize_text_field($_POST['sender_name']),
            'sender_phone' => sanitize_text_field($_POST['sender_phone']),
            'recipient_name' => sanitize_text_field($_POST['recipient_name']),
            'recipient_phone' => sanitize_text_field($_POST['recipient_phone']),
            'recipient_address' => sanitize_textarea_field($_POST['recipient_address']),
            'origin_country_id' => !empty($_POST['origin_country_id']) ? intval($_POST['origin_country_id']) : null,
            'destination_country_id' => !empty($_POST['destination_country_id']) ? intval($_POST['destination_country_id']) : null,
            'transport_mode_id' => !empty($_POST['transport_mode_id']) ? intval($_POST['transport_mode_id']) : null,
            'weight' => floatval($_POST['weight']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => 'En attente',
            'approval_status' => 'pending', // IMPORTANT: Soumis à validation
            'created_by' => $user->ID,
            'created_at' => current_time('mysql')
        );

        // Insérer dans la base
        $result = $wpdb->insert(
            $wpdb->prefix . 'colis224_parcels',
            $data
        );

        if ($result) {
            $parcel_id = $wpdb->insert_id;

            // Logger l'action
            require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-migration.php';
            Colis224_DB_Migration::log_approval_action('parcel', $parcel_id, 'submitted');

            wp_send_json_success(array(
                'message' => 'Colis soumis avec succès ! En attente de validation par l\'administrateur.'
            ));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la création du colis'));
        }
    }

    /**
     * AJAX: Créer un départ (soumis à validation)
     */
    public function ajax_create_departure() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_agent_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier les permissions
        if (!$this->is_agent()) {
            wp_send_json_error(array('message' => '⛔ Accès refusé'));
            return;
        }

        global $wpdb;
        $user = wp_get_current_user();

        // Récupérer les données
        $data = array(
            'departure_number' => sanitize_text_field($_POST['departure_number']),
            'departure_city' => sanitize_text_field($_POST['departure_city']),
            'departure_country' => sanitize_text_field($_POST['departure_country']),
            'arrival_city' => sanitize_text_field($_POST['arrival_city']),
            'arrival_country' => sanitize_text_field($_POST['arrival_country']),
            'departure_date' => sanitize_text_field($_POST['departure_date']),
            'departure_time' => !empty($_POST['departure_time']) ? sanitize_text_field($_POST['departure_time']) : null,
            'transport_type' => sanitize_text_field($_POST['transport_type']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => 'scheduled',
            'approval_status' => 'pending', // IMPORTANT: Soumis à validation
            'created_by' => $user->ID,
            'is_active' => 1
        );

        // Insérer dans la base
        $result = $wpdb->insert(
            $wpdb->prefix . 'colis224_departures',
            $data
        );

        if ($result) {
            $departure_id = $wpdb->insert_id;

            // Logger l'action
            require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-migration.php';
            Colis224_DB_Migration::log_approval_action('departure', $departure_id, 'submitted');

            wp_send_json_success(array(
                'message' => 'Départ soumis avec succès ! En attente de validation par l\'administrateur.'
            ));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la création du départ'));
        }
    }

    /**
     * AJAX: Récupérer les items en attente
     */
    public function ajax_get_pending_items() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_agent_portal')) {
            wp_send_json_error(array('message' => 'Nonce invalide'));
            return;
        }

        // Vérifier les permissions
        if (!$this->is_agent()) {
            wp_send_json_error(array('message' => '⛔ Accès refusé'));
            return;
        }

        global $wpdb;
        $user = wp_get_current_user();

        $pending_parcels_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}colis224_parcels
            WHERE approval_status = 'pending'
            AND created_by = %d
        ", $user->ID));

        $pending_departures_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}colis224_departures
            WHERE approval_status = 'pending'
            AND created_by = %d
        ", $user->ID));

        wp_send_json_success(array(
            'parcels' => $pending_parcels_count,
            'departures' => $pending_departures_count,
            'total' => $pending_parcels_count + $pending_departures_count
        ));
    }
}
