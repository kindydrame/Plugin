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

        // Nouveaux AJAX handlers v2.18.2
        add_action('wp_ajax_colis224_agent_search_clients', array($this, 'ajax_search_clients'));
        add_action('wp_ajax_colis224_agent_create_client', array($this, 'ajax_create_client'));
        add_action('wp_ajax_colis224_agent_upload_photos', array($this, 'ajax_upload_photos'));

        // Enqueue scripts et styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue scripts et styles pour le portail agent
     */
    public function enqueue_scripts() {
        // Seulement si on est sur une page avec le shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'colis224_agent_portal')) {
            // CSS
            wp_enqueue_style(
                'colis224-agent-portal',
                COLIS224_PLUGIN_URL . 'assets/css/agent-portal.css',
                array(),
                COLIS224_VERSION
            );

            // JavaScript
            wp_enqueue_script(
                'colis224-agent-portal',
                COLIS224_PLUGIN_URL . 'assets/js/agent-portal.js',
                array('jquery'),
                COLIS224_VERSION,
                true
            );

            // Localize script pour AJAX
            wp_localize_script('colis224-agent-portal', 'colis224_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('colis224_agent_portal')
            ));
        }
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
        $countries = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_countries WHERE is_active = 1 ORDER BY name");
        $transport_modes = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_transport_modes ORDER BY name");

        // Générer numéro de facture automatique
        $prefix = get_option('colis224_parcel_prefix', 'PA');
        $next_number = $wpdb->get_var("SELECT MAX(CAST(SUBSTRING(tracking_number, LENGTH('$prefix')+1) AS UNSIGNED)) FROM {$wpdb->prefix}colis224_parcels") + 1;
        $suggested_tracking = $prefix . str_pad($next_number, 6, '0', STR_PAD_LEFT);

        // Générer numéro de facture unique
        $invoice_number = strtoupper(substr(md5(uniqid()), 0, 4));

        ?>
        <div class="agent-form">
            <h3>📦 Nouveau Colis (Soumission à Validation)</h3>
            <p class="form-description">Remplissez tous les champs. Un récapitulatif vous sera présenté avant validation.</p>

            <form id="form-agent-create-parcel" class="colis224-form">

                <!-- Section 1: Informations Client -->
                <div class="form-section">
                    <h4>👤 Informations Client</h4>

                    <div class="form-row">
                        <div class="form-group form-group-full">
                            <label for="client_search">Rechercher Client *</label>
                            <div class="autocomplete-wrapper">
                                <input type="text"
                                       id="client_search"
                                       placeholder="Tapez nom, prénom, téléphone ou email..."
                                       autocomplete="off">
                                <div id="client_autocomplete_results" class="autocomplete-results"></div>
                                <button type="button" id="btn-new-client" class="btn-secondary">
                                    <span class="dashicons dashicons-plus"></span> Nouveau Client
                                </button>
                            </div>
                            <input type="hidden" name="client_id" id="client_id">
                            <p class="help-text">Recherchez un client existant ou créez-en un nouveau</p>
                        </div>
                    </div>

                    <!-- Formulaire nouveau client (caché par défaut) -->
                    <div id="new-client-form" style="display: none;" class="subsection">
                        <h5>➕ Créer un Nouveau Client</h5>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_client_name">Nom & Prénom *</label>
                                <input type="text" id="new_client_name" placeholder="Ex: Jean Dupont">
                            </div>

                            <div class="form-group">
                                <label for="new_client_phone">Téléphone *</label>
                                <input type="tel" id="new_client_phone" placeholder="Ex: +224 XXX XXX XXX">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_client_email">Email</label>
                                <input type="email" id="new_client_email" placeholder="email@exemple.com">
                            </div>

                            <div class="form-group">
                                <label for="new_client_id_card">N° Carte d'Identité</label>
                                <input type="text" id="new_client_id_card" placeholder="Ex: CN123456">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_client_address">Adresse</label>
                            <textarea id="new_client_address" rows="2" placeholder="Adresse complète du client"></textarea>
                        </div>

                        <button type="button" id="btn-save-new-client" class="btn-primary">
                            <span class="dashicons dashicons-yes"></span> Créer ce Client
                        </button>
                        <button type="button" id="btn-cancel-new-client" class="btn-secondary">
                            Annuler
                        </button>
                    </div>

                    <!-- Affichage infos client sélectionné -->
                    <div id="selected-client-info" style="display: none;" class="client-info-box">
                        <h5>✅ Client Sélectionné</h5>
                        <div id="selected-client-details"></div>
                        <button type="button" id="btn-change-client" class="btn-link">
                            Changer de client
                        </button>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="invoice_number">N° Facture *</label>
                            <input type="text"
                                   name="invoice_number"
                                   id="invoice_number"
                                   value="<?php echo esc_attr($invoice_number); ?>"
                                   readonly
                                   style="background-color: #f5f5f5;">
                            <p class="help-text">Généré automatiquement</p>
                        </div>

                        <div class="form-group">
                            <label for="tracking_number">N° Suivi (PA) *</label>
                            <input type="text"
                                   name="tracking_number"
                                   id="tracking_number"
                                   value="<?php echo esc_attr($suggested_tracking); ?>"
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Expéditeur -->
                <div class="form-section">
                    <h4>📤 Informations Expéditeur</h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sender_name">Prénom & Nom Expéditeur</label>
                            <input type="text" name="sender_name" id="sender_name" placeholder="Ex: Marie Konaté">
                        </div>

                        <div class="form-group">
                            <label for="sender_phone">Téléphone Expéditeur</label>
                            <input type="tel" name="sender_phone" id="sender_phone" placeholder="Ex: +33 6 XX XX XX XX">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sender_id_card">N° Carte d'Identité Nationale</label>
                            <input type="text" name="sender_id_card" id="sender_id_card" placeholder="Ex: CN123456789">
                        </div>

                        <div class="form-group">
                            <label for="sender_email">Email Expéditeur</label>
                            <input type="email" name="sender_email" id="sender_email" placeholder="email@exemple.com">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Destinataire -->
                <div class="form-section">
                    <h4>📥 Informations Destinataire</h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="recipient_name">Prénom & Nom Destinataire *</label>
                            <input type="text" name="recipient_name" id="recipient_name" required placeholder="Ex: Amadou Diallo">
                        </div>

                        <div class="form-group">
                            <label for="recipient_phone">Téléphone Destinataire *</label>
                            <input type="tel" name="recipient_phone" id="recipient_phone" required placeholder="Ex: +224 XXX XXX XXX">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="recipient_email">Email Destinataire</label>
                            <input type="email" name="recipient_email" id="recipient_email" placeholder="email@exemple.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="recipient_address">Adresse Destinataire du Colis *</label>
                        <textarea name="recipient_address" id="recipient_address" required rows="3" placeholder="Adresse complète de livraison"></textarea>
                    </div>
                </div>

                <!-- Section 4: Détails du Colis -->
                <div class="form-section">
                    <h4>📦 Détails du Colis</h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="origin_country_id">Pays de Provenance *</label>
                            <select name="origin_country_id" id="origin_country_id" required>
                                <option value="">Sélectionner un pays</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo $country->id; ?>">
                                        <?php echo esc_html($country->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="destination_country_id">Pays de Destination *</label>
                            <select name="destination_country_id" id="destination_country_id" required>
                                <option value="">Sélectionner un pays</option>
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
                            <label for="parcel_nature">Nature du Colis *</label>
                            <input type="text" name="parcel_nature" id="parcel_nature" required placeholder="Ex: Vêtements, Électronique, Documents...">
                            <p class="help-text">Décrivez le contenu du colis</p>
                        </div>

                        <div class="form-group">
                            <label for="weight">Poids du Colis (kg) *</label>
                            <input type="number" step="0.01" name="weight" id="weight" required placeholder="Ex: 5.5">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price_eur">Tarif du Colis en € *</label>
                            <input type="number" step="0.01" name="price_eur" id="price_eur" required placeholder="Ex: 50.00">
                        </div>

                        <div class="form-group">
                            <label for="price_gnf">Tarif du Colis en GNF *</label>
                            <input type="number" step="1" name="price_gnf" id="price_gnf" required placeholder="Ex: 550000">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_status">État de Paiement *</label>
                            <select name="payment_status" id="payment_status" required>
                                <option value="Non payé">Non payé</option>
                                <option value="Payé">Payé</option>
                                <option value="Partiel">Partiel</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="estimated_delivery_date">Délai Estimatif de Livraison</label>
                            <input type="date" name="estimated_delivery_date" id="estimated_delivery_date">
                        </div>
                    </div>

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
                </div>

                <!-- Section 5: Photos -->
                <div class="form-section">
                    <h4>📸 Photos</h4>

                    <div class="form-group">
                        <label for="parcel_photos">Photos du Colis</label>
                        <input type="file"
                               id="parcel_photos"
                               accept="image/*"
                               multiple
                               class="file-input">
                        <p class="help-text">Vous pouvez sélectionner plusieurs photos (JPG, PNG)</p>
                        <div id="parcel_photos_preview" class="photos-preview"></div>
                    </div>

                    <div class="form-group">
                        <label for="receipt_photos">Photos du Reçu</label>
                        <input type="file"
                               id="receipt_photos"
                               accept="image/*"
                               multiple
                               class="file-input">
                        <p class="help-text">Photos du reçu papier signé</p>
                        <div id="receipt_photos_preview" class="photos-preview"></div>
                    </div>
                </div>

                <!-- Section 6: Notes -->
                <div class="form-section">
                    <h4>📝 Notes Complémentaires</h4>

                    <div class="form-group">
                        <label for="notes">Remarques ou Instructions Spéciales</label>
                        <textarea name="notes" id="notes" rows="4" placeholder="Notes additionnelles..."></textarea>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="form-actions">
                    <button type="button" id="btn-preview-parcel" class="colis224-btn colis224-btn-primary">
                        <span class="dashicons dashicons-visibility"></span> Vérifier & Soumettre
                    </button>
                    <button type="button" class="colis224-btn colis224-btn-secondary" onclick="document.getElementById('form-agent-create-parcel').reset();">
                        <span class="dashicons dashicons-undo"></span> Réinitialiser
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal de Récapitulatif -->
        <div id="modal-parcel-preview" class="colis224-modal" style="display: none;">
            <div class="colis224-modal-content modal-large">
                <span class="colis224-modal-close">&times;</span>
                <h3>🔍 Vérification des Informations</h3>
                <p class="modal-subtitle">Vérifiez attentivement les informations avant de soumettre. Une fois soumis, seul l'administrateur pourra modifier.</p>

                <div id="parcel-preview-content" class="preview-content">
                    <!-- Contenu généré dynamiquement -->
                </div>

                <div class="modal-actions">
                    <button type="button" id="btn-confirm-submit-parcel" class="colis224-btn colis224-btn-success">
                        <span class="dashicons dashicons-yes"></span> Confirmer et Soumettre
                    </button>
                    <button type="button" id="btn-edit-parcel" class="colis224-btn colis224-btn-secondary">
                        <span class="dashicons dashicons-edit"></span> Modifier
                    </button>
                </div>
            </div>
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

        // Récupérer et valider les données obligatoires
        if (empty($_POST['client_id']) || empty($_POST['tracking_number']) ||
            empty($_POST['recipient_name']) || empty($_POST['recipient_phone']) ||
            empty($_POST['recipient_address']) || empty($_POST['parcel_nature'])) {
            wp_send_json_error(array('message' => '❌ Veuillez remplir tous les champs obligatoires'));
            return;
        }

        // Vérifier que le client existe
        $client_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}colis224_clients WHERE id = %d",
            intval($_POST['client_id'])
        ));

        if (!$client_exists) {
            wp_send_json_error(array('message' => '❌ Client introuvable'));
            return;
        }

        // Récupérer le code client
        $client_code = $wpdb->get_var($wpdb->prepare(
            "SELECT client_code FROM {$wpdb->prefix}colis224_clients WHERE id = %d",
            intval($_POST['client_id'])
        ));

        // Calculer total_amount (utiliser prix EUR comme référence)
        $price_eur = !empty($_POST['price_eur']) ? floatval($_POST['price_eur']) : 0;
        $price_gnf = !empty($_POST['price_gnf']) ? floatval($_POST['price_gnf']) : 0;

        // Si prix EUR fourni, l'utiliser, sinon convertir GNF en EUR
        if ($price_eur > 0) {
            $total_amount = $price_eur;
            $currency = 'EUR';
        } else if ($price_gnf > 0) {
            $exchange_rate = get_option('colis224_exchange_rate_eur', 11000);
            $total_amount = $price_gnf / $exchange_rate;
            $currency = 'GNF';
        } else {
            $total_amount = 0;
            $currency = 'EUR';
        }

        // Préparer les données pour insertion
        $data = array(
            'invoice_number' => sanitize_text_field($_POST['invoice_number']),
            'tracking_number' => sanitize_text_field($_POST['tracking_number']),
            'client_id' => intval($_POST['client_id']),
            'client_code' => $client_code,

            // Expéditeur
            'sender_name' => sanitize_text_field($_POST['sender_name']),
            'sender_phone' => sanitize_text_field($_POST['sender_phone']),
            'sender_id_card' => sanitize_text_field($_POST['sender_id_card']),
            'sender_email' => sanitize_email($_POST['sender_email']),

            // Destinataire
            'recipient_name' => sanitize_text_field($_POST['recipient_name']),
            'recipient_phone' => sanitize_text_field($_POST['recipient_phone']),
            'recipient_email' => sanitize_email($_POST['recipient_email']),
            'recipient_address' => sanitize_textarea_field($_POST['recipient_address']),

            // Détails du colis
            'origin_country_id' => !empty($_POST['origin_country_id']) ? intval($_POST['origin_country_id']) : null,
            'destination_country_id' => !empty($_POST['destination_country_id']) ? intval($_POST['destination_country_id']) : null,
            'transport_mode_id' => !empty($_POST['transport_mode_id']) ? intval($_POST['transport_mode_id']) : null,
            'parcel_nature' => sanitize_text_field($_POST['parcel_nature']),
            'weight' => floatval($_POST['weight']),

            // Tarification
            'total_amount' => $total_amount,
            'currency' => $currency,
            'payment_status' => sanitize_text_field($_POST['payment_status']),

            // Dates
            'estimated_delivery_date' => !empty($_POST['estimated_delivery_date']) ? sanitize_text_field($_POST['estimated_delivery_date']) : null,
            'reception_date' => current_time('mysql', false),

            // Photos (URLs séparées par des virgules)
            'photos' => !empty($_POST['parcel_photos']) ? sanitize_textarea_field($_POST['parcel_photos']) : null,
            'receipt_photos' => !empty($_POST['receipt_photos']) ? sanitize_textarea_field($_POST['receipt_photos']) : null,

            // Notes
            'notes' => sanitize_textarea_field($_POST['notes']),

            // Statuts
            'status' => 'En attente',
            'approval_status' => 'pending', // IMPORTANT: Soumis à validation

            // Traçabilité
            'created_by' => $user->ID,
            'created_at' => current_time('mysql')
        );

        // Insérer dans la base
        $result = $wpdb->insert(
            $wpdb->prefix . 'colis224_parcels',
            $data,
            array(
                '%s', '%s', '%d', '%s',  // invoice, tracking, client_id, client_code
                '%s', '%s', '%s', '%s',  // sender_name, phone, id_card, email
                '%s', '%s', '%s', '%s',  // recipient_name, phone, email, address
                '%d', '%d', '%d', '%s', '%f',  // countries, transport, nature, weight
                '%f', '%s', '%s',  // amount, currency, payment_status
                '%s', '%s',  // dates
                '%s', '%s',  // photos
                '%s',  // notes
                '%s', '%s',  // status, approval_status
                '%d', '%s'  // created_by, created_at
            )
        );

        if ($result) {
            $parcel_id = $wpdb->insert_id;

            // Logger l'action
            require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-migration.php';
            Colis224_DB_Migration::log_approval_action('parcel', $parcel_id, 'submitted');

            wp_send_json_success(array(
                'message' => '✅ Colis soumis avec succès ! En attente de validation par l\'administrateur.',
                'parcel_id' => $parcel_id,
                'tracking_number' => $data['tracking_number'],
                'invoice_number' => $data['invoice_number']
            ));
        } else {
            wp_send_json_error(array('message' => '❌ Erreur lors de la création du colis: ' . $wpdb->last_error));
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

    /**
     * AJAX: Recherche de clients (autocomplete)
     */
    public function ajax_search_clients() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_agent_portal')) {
            wp_send_json_error(array('message' => 'Vérification de sécurité échouée'));
            return;
        }

        // Vérifier les permissions
        if (!$this->is_agent()) {
            wp_send_json_error(array('message' => '⛔ Accès refusé'));
            return;
        }

        global $wpdb;

        $search = sanitize_text_field($_POST['search']);

        // Recherche dans nom, téléphone, email
        $clients = $wpdb->get_results($wpdb->prepare("
            SELECT id, name, phone, email, address, id_card, client_code
            FROM {$wpdb->prefix}colis224_clients
            WHERE name LIKE %s
               OR phone LIKE %s
               OR email LIKE %s
            ORDER BY name
            LIMIT 10
        ", '%' . $wpdb->esc_like($search) . '%',
           '%' . $wpdb->esc_like($search) . '%',
           '%' . $wpdb->esc_like($search) . '%'));

        wp_send_json_success(array('clients' => $clients));
    }

    /**
     * AJAX: Créer un nouveau client
     */
    public function ajax_create_client() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_agent_portal')) {
            wp_send_json_error(array('message' => 'Vérification de sécurité échouée'));
            return;
        }

        // Vérifier les permissions
        if (!$this->is_agent()) {
            wp_send_json_error(array('message' => '⛔ Accès refusé : Réservé aux agents.'));
            return;
        }

        global $wpdb;

        // Données du client
        $name = sanitize_text_field($_POST['name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $address = sanitize_textarea_field($_POST['address']);
        $id_card = sanitize_text_field($_POST['id_card']);

        // Vérifier que nom et téléphone sont fournis
        if (empty($name) || empty($phone)) {
            wp_send_json_error(array('message' => 'Nom et téléphone sont obligatoires'));
            return;
        }

        // Vérifier si le téléphone existe déjà
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}colis224_clients WHERE phone = %s",
            $phone
        ));

        if ($existing) {
            wp_send_json_error(array('message' => 'Ce numéro de téléphone est déjà enregistré'));
            return;
        }

        // Générer un code client unique (PA + 6 chiffres)
        $max_code = $wpdb->get_var("
            SELECT MAX(CAST(SUBSTRING(client_code, 3) AS UNSIGNED))
            FROM {$wpdb->prefix}colis224_clients
            WHERE client_code LIKE 'PA%'
        ");
        $next_code_number = ($max_code ? $max_code : 0) + 1;
        $client_code = 'PA' . str_pad($next_code_number, 6, '0', STR_PAD_LEFT);

        // Insérer le client
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'colis224_clients',
            array(
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'id_card' => $id_card,
                'client_code' => $client_code,
                'is_active' => 1,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        if ($inserted === false) {
            wp_send_json_error(array('message' => 'Erreur lors de la création du client'));
            return;
        }

        $client_id = $wpdb->insert_id;

        // Récupérer le client créé
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_clients WHERE id = %d",
            $client_id
        ));

        wp_send_json_success(array(
            'message' => '✅ Client créé avec succès',
            'client' => $client
        ));
    }

    /**
     * AJAX: Upload de photos
     */
    public function ajax_upload_photos() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_agent_portal')) {
            wp_send_json_error(array('message' => 'Vérification de sécurité échouée'));
            return;
        }

        // Vérifier les permissions
        if (!$this->is_agent()) {
            wp_send_json_error(array('message' => '⛔ Accès refusé'));
            return;
        }

        // Vérifier qu'un fichier a été uploadé
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'Aucun fichier fourni'));
            return;
        }

        // Utiliser la fonction WordPress pour gérer l'upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $uploadedfile = $_FILES['file'];
        $upload_overrides = array('test_form' => false);

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(array(
                'url' => $movefile['url'],
                'file' => $movefile['file']
            ));
        } else {
            wp_send_json_error(array('message' => $movefile['error']));
        }
    }
}
