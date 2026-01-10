<?php
/**
 * Interface Admin pour la gestion des départs
 *
 * @package Colis224
 * @subpackage Admin
 * @since 2.10.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Departures_Admin {

    /**
     * Afficher la page admin des départs
     */
    public static function display_page() {
        global $wpdb;

        // Traiter l'ajout/modification
        if (isset($_POST['action']) && $_POST['action'] === 'save_departure') {
            check_admin_referer('colis224_save_departure');

            $departure_data = array(
                'departure_city' => sanitize_text_field($_POST['departure_city']),
                'departure_country' => sanitize_text_field($_POST['departure_country']),
                'departure_country_code' => sanitize_text_field($_POST['departure_country_code']),
                'arrival_city' => sanitize_text_field($_POST['arrival_city']),
                'arrival_country' => sanitize_text_field($_POST['arrival_country']),
                'arrival_country_code' => sanitize_text_field($_POST['arrival_country_code']),
                'departure_date' => sanitize_text_field($_POST['departure_date']),
                'departure_time' => sanitize_text_field($_POST['departure_time']),
                'transport_type' => sanitize_text_field($_POST['transport_type']),
                'estimated_duration' => sanitize_text_field($_POST['estimated_duration']),
                'available_seats' => !empty($_POST['available_seats']) ? intval($_POST['available_seats']) : null,
                'price_estimate' => !empty($_POST['price_estimate']) ? floatval($_POST['price_estimate']) : null,
                'currency' => sanitize_text_field($_POST['currency']),
                'status' => sanitize_text_field($_POST['status']),
                'notes' => sanitize_textarea_field($_POST['notes']),
                'whatsapp_number' => sanitize_text_field($_POST['whatsapp_number']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            );

            if (!empty($_POST['departure_id'])) {
                $wpdb->update(
                    $wpdb->prefix . 'colis224_departures',
                    $departure_data,
                    array('id' => intval($_POST['departure_id']))
                );
                echo '<div class="notice notice-success"><p>✅ Départ mis à jour avec succès !</p></div>';
            } else {
                Colis224_Departures::add_departure($departure_data);
                echo '<div class="notice notice-success"><p>✅ Départ ajouté avec succès !</p></div>';
            }
        }

        // Traiter la suppression
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('colis224_delete_departure_' . $_GET['id']);

            $wpdb->delete(
                $wpdb->prefix . 'colis224_departures',
                array('id' => intval($_GET['id']))
            );
            echo '<div class="notice notice-success"><p>✅ Départ supprimé !</p></div>';
        }

        // Récupérer un départ pour édition
        $editing_departure = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $editing_departure = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}colis224_departures WHERE id = %d",
                intval($_GET['id'])
            ));
        }

        // Récupérer tous les départs
        $departures = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}colis224_departures ORDER BY departure_date DESC, created_at DESC LIMIT 100"
        );

        $countries = Colis224_Departures::get_countries();

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🚀 Gestion des Départs</h1>
            <a href="<?php echo admin_url('admin.php?page=colis224-departures'); ?>" class="page-title-action">➕ Nouveau départ</a>
            <hr class="wp-header-end">

            <div class="colis224-admin-container">
                <div class="colis224-row">
                    <!-- Formulaire d'ajout/édition -->
                    <div class="colis224-col-4">
                        <div class="colis224-box">
                            <h2><?php echo $editing_departure ? '✏️ Modifier le départ' : '➕ Ajouter un départ'; ?></h2>

                            <form method="post" action="">
                                <?php wp_nonce_field('colis224_save_departure'); ?>
                                <input type="hidden" name="action" value="save_departure">
                                <?php if ($editing_departure): ?>
                                    <input type="hidden" name="departure_id" value="<?php echo $editing_departure->id; ?>">
                                <?php endif; ?>

                                <table class="form-table">
                                    <tr>
                                        <th colspan="2"><strong>📍 DÉPART</strong></th>
                                    </tr>
                                    <tr>
                                        <th>Ville de départ *</th>
                                        <td>
                                            <input type="text" name="departure_city" class="regular-text" required
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->departure_city) : ''; ?>"
                                                placeholder="Ex: Conakry, Guangzhou, Paris...">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Pays *</th>
                                        <td>
                                            <select name="departure_country_code" required>
                                                <option value="">-- Sélectionner --</option>
                                                <?php foreach ($countries as $code => $country): ?>
                                                    <option value="<?php echo esc_attr($code); ?>"
                                                        data-country="<?php echo esc_attr($country['name']); ?>"
                                                        <?php echo ($editing_departure && $editing_departure->departure_country_code === $code) ? 'selected' : ''; ?>>
                                                        <?php echo $country['flag'] . ' ' . esc_html($country['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="departure_country"
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->departure_country) : ''; ?>">
                                        </td>
                                    </tr>

                                    <tr>
                                        <th colspan="2"><strong>🎯 ARRIVÉE</strong></th>
                                    </tr>
                                    <tr>
                                        <th>Ville d'arrivée *</th>
                                        <td>
                                            <input type="text" name="arrival_city" class="regular-text" required
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->arrival_city) : ''; ?>"
                                                placeholder="Ex: Conakry, Guangzhou, Paris...">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Pays *</th>
                                        <td>
                                            <select name="arrival_country_code" required>
                                                <option value="">-- Sélectionner --</option>
                                                <?php foreach ($countries as $code => $country): ?>
                                                    <option value="<?php echo esc_attr($code); ?>"
                                                        data-country="<?php echo esc_attr($country['name']); ?>"
                                                        <?php echo ($editing_departure && $editing_departure->arrival_country_code === $code) ? 'selected' : ''; ?>>
                                                        <?php echo $country['flag'] . ' ' . esc_html($country['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="arrival_country"
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->arrival_country) : ''; ?>">
                                        </td>
                                    </tr>

                                    <tr>
                                        <th colspan="2"><strong>📅 INFORMATIONS DU VOYAGE</strong></th>
                                    </tr>
                                    <tr>
                                        <th>Date de départ *</th>
                                        <td>
                                            <input type="date" name="departure_date" required
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->departure_date) : ''; ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Heure de départ</th>
                                        <td>
                                            <input type="time" name="departure_time"
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->departure_time) : '08:00'; ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Type de transport *</th>
                                        <td>
                                            <label style="margin-right: 20px;">
                                                <input type="radio" name="transport_type" value="plane" required
                                                    <?php echo (!$editing_departure || $editing_departure->transport_type === 'plane') ? 'checked' : ''; ?>>
                                                ✈️ Avion
                                            </label>
                                            <label>
                                                <input type="radio" name="transport_type" value="boat"
                                                    <?php echo ($editing_departure && $editing_departure->transport_type === 'boat') ? 'checked' : ''; ?>>
                                                🚢 Bateau
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Durée estimée</th>
                                        <td>
                                            <input type="text" name="estimated_duration" class="regular-text"
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->estimated_duration) : ''; ?>"
                                                placeholder="Ex: 12 heures, 5 jours">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>kg disponible</th>
                                        <td>
                                            <input type="number" name="available_seats" min="0"
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->available_seats) : ''; ?>"
                                                placeholder="Laisser vide si illimité">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Prix estimatif</th>
                                        <td>
                                            <input type="number" name="price_estimate" step="0.01" min="0"
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->price_estimate) : ''; ?>"
                                                placeholder="0.00">
                                            <select name="currency">
                                                <option value="GNF" <?php echo (!$editing_departure || $editing_departure->currency === 'GNF') ? 'selected' : ''; ?>>GNF</option>
                                                <option value="EUR" <?php echo ($editing_departure && $editing_departure->currency === 'EUR') ? 'selected' : ''; ?>>EUR</option>
                                                <option value="USD" <?php echo ($editing_departure && $editing_departure->currency === 'USD') ? 'selected' : ''; ?>>USD</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Statut</th>
                                        <td>
                                            <select name="status">
                                                <option value="scheduled" <?php echo (!$editing_departure || $editing_departure->status === 'scheduled') ? 'selected' : ''; ?>>Programmé</option>
                                                <option value="departed" <?php echo ($editing_departure && $editing_departure->status === 'departed') ? 'selected' : ''; ?>>Parti</option>
                                                <option value="arrived" <?php echo ($editing_departure && $editing_departure->status === 'arrived') ? 'selected' : ''; ?>>Arrivé</option>
                                                <option value="delayed" <?php echo ($editing_departure && $editing_departure->status === 'delayed') ? 'selected' : ''; ?>>Retardé</option>
                                                <option value="cancelled" <?php echo ($editing_departure && $editing_departure->status === 'cancelled') ? 'selected' : ''; ?>>Annulé</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Numéro WhatsApp</th>
                                        <td>
                                            <input type="text" name="whatsapp_number" class="regular-text"
                                                value="<?php echo $editing_departure ? esc_attr($editing_departure->whatsapp_number) : '+224620178930'; ?>"
                                                placeholder="+224620178930">
                                            <p class="description">Format international avec +</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td>
                                            <textarea name="notes" rows="3" class="large-text"><?php echo $editing_departure ? esc_textarea($editing_departure->notes) : ''; ?></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Actif</th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="is_active" value="1"
                                                    <?php echo (!$editing_departure || $editing_departure->is_active) ? 'checked' : ''; ?>>
                                                Afficher sur le site
                                            </label>
                                        </td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <button type="submit" class="button button-primary">
                                        <?php echo $editing_departure ? '💾 Mettre à jour' : '➕ Ajouter le départ'; ?>
                                    </button>
                                    <?php if ($editing_departure): ?>
                                        <a href="<?php echo admin_url('admin.php?page=colis224-departures'); ?>" class="button">Annuler</a>
                                    <?php endif; ?>
                                </p>
                            </form>
                        </div>

                        <div class="colis224-box" style="margin-top: 20px;">
                            <h3>📌 Shortcode</h3>
                            <p>Utilisez ce shortcode pour afficher les départs sur votre site :</p>
                            <code style="display: block; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                                [colis224_departures]
                            </code>
                            <p style="margin-top: 10px;"><strong>Options :</strong></p>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <li><code>transport_type="plane"</code> - Afficher seulement les vols</li>
                                <li><code>transport_type="boat"</code> - Afficher seulement les bateaux</li>
                                <li><code>layout="grid"</code> - Affichage en grille</li>
                                <li><code>show_filters="no"</code> - Masquer les filtres</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Liste des départs -->
                    <div class="colis224-col-8">
                        <div class="colis224-box">
                            <h2>📋 Liste des départs (<?php echo count($departures); ?>)</h2>

                            <?php if (empty($departures)): ?>
                                <p>Aucun départ enregistré. Ajoutez votre premier départ ci-contre.</p>
                            <?php else: ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Transport</th>
                                            <th>Trajet</th>
                                            <th>Date/Heure</th>
                                            <th>Places</th>
                                            <th>Prix</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($departures as $dep):
                                            $transport_icon = $dep->transport_type === 'plane' ? '✈️' : '🚢';
                                            $departure_flag = isset($countries[$dep->departure_country_code]) ? $countries[$dep->departure_country_code]['flag'] : '🏳️';
                                            $arrival_flag = isset($countries[$dep->arrival_country_code]) ? $countries[$dep->arrival_country_code]['flag'] : '🏳️';

                                            $status_colors = array(
                                                'scheduled' => '#2271b1',
                                                'departed' => '#00a32a',
                                                'arrived' => '#00a32a',
                                                'delayed' => '#dba617',
                                                'cancelled' => '#d63638'
                                            );
                                            $status_labels = array(
                                                'scheduled' => 'Programmé',
                                                'departed' => 'Parti',
                                                'arrived' => 'Arrivé',
                                                'delayed' => 'Retardé',
                                                'cancelled' => 'Annulé'
                                            );
                                        ?>
                                            <tr <?php echo !$dep->is_active ? 'style="opacity:0.5;"' : ''; ?>>
                                                <td><?php echo $transport_icon; ?></td>
                                                <td>
                                                    <strong><?php echo $departure_flag . ' ' . esc_html($dep->departure_city); ?></strong>
                                                    →
                                                    <strong><?php echo $arrival_flag . ' ' . esc_html($dep->arrival_city); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($dep->departure_date)); ?><br>
                                                    <small><?php echo $dep->departure_time ? date('H:i', strtotime($dep->departure_time)) : '—'; ?></small>
                                                </td>
                                                <td><?php echo $dep->available_seats ? $dep->available_seats : '∞'; ?></td>
                                                <td>
                                                    <?php if ($dep->price_estimate): ?>
                                                        <?php echo number_format($dep->price_estimate, 0, ',', ' ') . ' ' . $dep->currency; ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span style="padding: 2px 8px; border-radius: 3px; background: <?php echo $status_colors[$dep->status]; ?>; color: white; font-size: 11px;">
                                                        <?php echo $status_labels[$dep->status]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?php echo admin_url('admin.php?page=colis224-departures&action=edit&id=' . $dep->id); ?>" class="button button-small">✏️ Modifier</a>
                                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=colis224-departures&action=delete&id=' . $dep->id), 'colis224_delete_departure_' . $dep->id); ?>"
                                                       class="button button-small"
                                                       onclick="return confirm('Voulez-vous vraiment supprimer ce départ ?');">🗑️ Supprimer</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Auto-remplir le nom du pays lors de la sélection
            $('select[name="departure_country_code"]').on('change', function() {
                var countryName = $(this).find(':selected').data('country');
                $('input[name="departure_country"]').val(countryName);
            });

            $('select[name="arrival_country_code"]').on('change', function() {
                var countryName = $(this).find(':selected').data('country');
                $('input[name="arrival_country"]').val(countryName);
            });
        });
        </script>
        <?php
    }
}
