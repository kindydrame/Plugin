<?php
/**
 * Interface de gestion des lots internationaux
 *
 * @package Colis224_Logistics
 * @subpackage Admin
 * @version 2.14.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Batches_Admin {

    /**
     * Afficher la page
     */
    public static function display_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes.'));
        }

        // Traiter les actions
        if (isset($_POST['action'])) {
            self::handle_actions();
        }

        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
        $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

        if ($view === 'create') {
            self::display_create_form();
        } elseif ($view === 'detail' && $batch_id > 0) {
            self::display_batch_detail($batch_id);
        } else {
            self::display_batches_list();
        }
    }

    /**
     * Afficher la liste des lots
     */
    private static function display_batches_list() {
        $filters = array(
            'status' => isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '',
            'type' => isset($_GET['type_filter']) ? sanitize_text_field($_GET['type_filter']) : '',
            'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : ''
        );

        $batches = Colis224_Batches::get_batches($filters);
        $stats = Colis224_Batches::get_statistics();

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-admin-multisite"></span>
                Gestion des Lots Internationaux
                <a href="?page=colis224-batches&view=create" class="page-title-action">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Nouveau Lot
                </a>
            </h1>

            <!-- Statistiques -->
            <div class="colis224-dashboard-grid colis224-grid-4">
                <div class="colis224-card colis224-card-blue">
                    <h4><span class="dashicons dashicons-admin-multisite"></span> Total Lots</h4>
                    <p class="colis224-big-number"><?php echo $stats['total']; ?></p>
                </div>
                <div class="colis224-card colis224-card-orange">
                    <h4><span class="dashicons dashicons-hammer"></span> En Préparation</h4>
                    <p class="colis224-big-number"><?php echo $stats['preparing']; ?></p>
                </div>
                <div class="colis224-card colis224-card-purple">
                    <h4><span class="dashicons dashicons-airplane"></span> En Transit</h4>
                    <p class="colis224-big-number"><?php echo $stats['in_transit']; ?></p>
                </div>
                <div class="colis224-card colis224-card-green">
                    <h4><span class="dashicons dashicons-yes-alt"></span> Arrivés</h4>
                    <p class="colis224-big-number"><?php echo $stats['arrived']; ?></p>
                </div>
            </div>

            <!-- Filtres -->
            <div class="colis224-card" style="margin-top: 20px;">
                <form method="get">
                    <input type="hidden" name="page" value="colis224-batches">

                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end;">
                        <div>
                            <label for="s">Recherche</label>
                            <input type="text" name="s" id="s" value="<?php echo esc_attr($filters['search']); ?>"
                                   placeholder="N° lot, conteneur, vol..."
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <label for="type_filter">Type</label>
                            <select name="type_filter" id="type_filter" style="width: 100%; padding: 8px;">
                                <option value="">Tous</option>
                                <option value="container" <?php selected($filters['type'], 'container'); ?>>Conteneur</option>
                                <option value="flight" <?php selected($filters['type'], 'flight'); ?>>Vol</option>
                            </select>
                        </div>
                        <div>
                            <label for="status_filter">Statut</label>
                            <select name="status_filter" id="status_filter" style="width: 100%; padding: 8px;">
                                <option value="">Tous</option>
                                <option value="preparing" <?php selected($filters['status'], 'preparing'); ?>>Préparation</option>
                                <option value="in_transit" <?php selected($filters['status'], 'in_transit'); ?>>En Transit</option>
                                <option value="arrived" <?php selected($filters['status'], 'arrived'); ?>>Arrivé</option>
                                <option value="customs" <?php selected($filters['status'], 'customs'); ?>>Douane</option>
                                <option value="cleared" <?php selected($filters['status'], 'cleared'); ?>>Dédouané</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="button button-primary">Filtrer</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Liste -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📋 Liste des Lots (<?php echo count($batches); ?>)</h2>

                <?php if (empty($batches)): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">Aucun lot trouvé.</p>
                <?php else: ?>
                    <table class="widefat" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>N° Lot</th>
                                <th>Type</th>
                                <th>Trajet</th>
                                <th>Conteneur/Vol</th>
                                <th>Départ</th>
                                <th>Arrivée</th>
                                <th>Colis</th>
                                <th>Poids</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            global $wpdb;
                            $table_countries = $wpdb->prefix . 'colis224_countries';

                            foreach ($batches as $batch):
                                $origin = $batch->origin_country_id ? $wpdb->get_var($wpdb->prepare("SELECT name FROM $table_countries WHERE id = %d", $batch->origin_country_id)) : '-';
                                $destination = $batch->destination_country_id ? $wpdb->get_var($wpdb->prepare("SELECT name FROM $table_countries WHERE id = %d", $batch->destination_country_id)) : '-';

                                $status_colors = array(
                                    'preparing' => '#f0a000',
                                    'in_transit' => '#2271b1',
                                    'arrived' => '#46b450',
                                    'customs' => '#f0a000',
                                    'cleared' => '#46b450',
                                    'closed' => '#999'
                                );

                                $status_labels = array(
                                    'preparing' => 'Préparation',
                                    'in_transit' => 'En Transit',
                                    'arrived' => 'Arrivé',
                                    'customs' => 'Douane',
                                    'cleared' => 'Dédouané',
                                    'closed' => 'Clôturé'
                                );
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($batch->batch_number); ?></strong></td>
                                    <td><?php echo $batch->batch_type === 'container' ? '🚢 Conteneur' : '✈️ Vol'; ?></td>
                                    <td><?php echo esc_html($origin) . ' → ' . esc_html($destination); ?></td>
                                    <td>
                                        <?php
                                        if ($batch->batch_type === 'container') {
                                            echo esc_html($batch->container_number ?: '-');
                                        } else {
                                            echo esc_html($batch->flight_number ?: '-');
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $batch->departure_date ? mysql2date('d/m/Y', $batch->departure_date) : '-'; ?></td>
                                    <td><?php echo $batch->estimated_arrival_date ? mysql2date('d/m/Y', $batch->estimated_arrival_date) : '-'; ?></td>
                                    <td><strong><?php echo $batch->total_parcels; ?></strong></td>
                                    <td><?php echo number_format($batch->total_weight, 2); ?> kg</td>
                                    <td>
                                        <span style="background: <?php echo $status_colors[$batch->status]; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                            <?php echo $status_labels[$batch->status]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?page=colis224-batches&view=detail&batch_id=<?php echo $batch->id; ?>" class="button button-small">
                                            <span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span>
                                            Voir
                                        </a>
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
     * Afficher le formulaire de création
     */
    private static function display_create_form() {
        global $wpdb;
        $countries = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_countries WHERE is_active = 1 ORDER BY name");

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-admin-multisite"></span>
                Créer un Nouveau Lot
                <a href="?page=colis224-batches" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Retour
                </a>
            </h1>

            <div class="colis224-card">
                <form method="post">
                    <input type="hidden" name="action" value="create_batch">
                    <?php wp_nonce_field('colis224_create_batch', 'batch_nonce'); ?>

                    <div class="colis224-form-grid">
                        <div class="colis224-form-group">
                            <label for="batch_type">Type de Lot *</label>
                            <select name="batch_type" id="batch_type" required>
                                <option value="">Sélectionner...</option>
                                <option value="container">🚢 Conteneur Maritime</option>
                                <option value="flight">✈️ Vol Cargo</option>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="transport_company">Compagnie de Transport</label>
                            <input type="text" name="transport_company" id="transport_company" placeholder="Ex: MAERSK, AIR FRANCE...">
                        </div>
                    </div>

                    <div class="colis224-form-grid">
                        <div class="colis224-form-group">
                            <label for="origin_country_id">Pays d'Origine</label>
                            <select name="origin_country_id" id="origin_country_id">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo $country->id; ?>"><?php echo esc_html($country->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="destination_country_id">Pays de Destination</label>
                            <select name="destination_country_id" id="destination_country_id">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo $country->id; ?>"><?php echo esc_html($country->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="colis224-form-grid" id="container_fields" style="display: none;">
                        <div class="colis224-form-group">
                            <label for="container_number">Numéro de Conteneur</label>
                            <input type="text" name="container_number" id="container_number" placeholder="Ex: MAEU1234567">
                        </div>
                    </div>

                    <div class="colis224-form-grid" id="flight_fields" style="display: none;">
                        <div class="colis224-form-group">
                            <label for="flight_number">Numéro de Vol</label>
                            <input type="text" name="flight_number" id="flight_number" placeholder="Ex: AF123">
                        </div>
                    </div>

                    <div class="colis224-form-grid">
                        <div class="colis224-form-group">
                            <label for="departure_date">Date de Départ Prévue</label>
                            <input type="date" name="departure_date" id="departure_date">
                        </div>

                        <div class="colis224-form-group">
                            <label for="estimated_arrival_date">Date d'Arrivée Prévue</label>
                            <input type="date" name="estimated_arrival_date" id="estimated_arrival_date">
                        </div>
                    </div>

                    <div class="colis224-form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="4"></textarea>
                    </div>

                    <div class="colis224-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            Créer le Lot
                        </button>
                        <a href="?page=colis224-batches" class="button button-large">Annuler</a>
                    </div>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#batch_type').on('change', function() {
                var type = $(this).val();
                $('#container_fields').hide();
                $('#flight_fields').hide();

                if (type === 'container') {
                    $('#container_fields').show();
                } else if (type === 'flight') {
                    $('#flight_fields').show();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Afficher le détail d'un lot
     */
    private static function display_batch_detail($batch_id) {
        $batch = Colis224_Batches::get_batch($batch_id);
        if (!$batch) {
            echo '<div class="notice notice-error"><p>Lot introuvable.</p></div>';
            return;
        }

        $parcels = Colis224_Batches::get_batch_parcels($batch_id);

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-admin-multisite"></span>
                Lot <?php echo esc_html($batch->batch_number); ?>
                <a href="?page=colis224-batches" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Retour
                </a>
            </h1>

            <!-- Info lot -->
            <div class="colis224-card">
                <h2>📊 Informations du Lot</h2>

                <table class="widefat">
                    <tr>
                        <td><strong>Numéro de Lot</strong></td>
                        <td><?php echo esc_html($batch->batch_number); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Type</strong></td>
                        <td><?php echo $batch->batch_type === 'container' ? '🚢 Conteneur Maritime' : '✈️ Vol Cargo'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Compagnie</strong></td>
                        <td><?php echo esc_html($batch->transport_company ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo $batch->batch_type === 'container' ? 'N° Conteneur' : 'N° Vol'; ?></strong></td>
                        <td><?php echo esc_html(($batch->batch_type === 'container' ? $batch->container_number : $batch->flight_number) ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Colis</strong></td>
                        <td><strong><?php echo $batch->total_parcels; ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Poids Total</strong></td>
                        <td><strong><?php echo number_format($batch->total_weight, 2); ?> kg</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Valeur Totale</strong></td>
                        <td><strong><?php echo number_format($batch->total_value, 0, ',', ' '); ?> GNF</strong></td>
                    </tr>
                </table>
            </div>

            <!-- Colis du lot -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📦 Colis Assignés (<?php echo count($parcels); ?>)</h2>

                <?php if (empty($parcels)): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">Aucun colis assigné à ce lot.</p>
                <?php else: ?>
                    <table class="widefat" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>N° Suivi</th>
                                <th>Client</th>
                                <th>Destinataire</th>
                                <th>Poids</th>
                                <th>Montant</th>
                                <th>Assigné le</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parcels as $parcel): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                                    <td><?php echo esc_html($parcel->client_name ?: '-'); ?></td>
                                    <td><?php echo esc_html($parcel->recipient_name); ?></td>
                                    <td><?php echo number_format($parcel->weight, 2); ?> kg</td>
                                    <td><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> GNF</td>
                                    <td><?php echo mysql2date('d/m/Y H:i', $parcel->assigned_at); ?></td>
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
     * Traiter les actions
     */
    private static function handle_actions() {
        if ($_POST['action'] === 'create_batch' && check_admin_referer('colis224_create_batch', 'batch_nonce')) {
            $result = Colis224_Batches::create_batch($_POST);

            if ($result['success']) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                echo '<script>window.location.href="?page=colis224-batches&view=detail&batch_id=' . $result['batch_id'] . '";</script>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }
    }
}
