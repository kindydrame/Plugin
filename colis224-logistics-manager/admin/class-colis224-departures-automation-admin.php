<?php
/**
 * Interface Admin pour l'automatisation des départs
 *
 * @package Colis224
 * @subpackage Admin
 * @since 2.10.6
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Departures_Automation_Admin {

    /**
     * Afficher la page admin de l'automatisation
     */
    public static function display_page() {
        global $wpdb;

        // Traiter les actions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'toggle_rule':
                    check_admin_referer('colis224_toggle_automation');
                    $rule_id = intval($_POST['rule_id']);
                    $is_active = intval($_POST['is_active']);
                    Colis224_Departures_Automation::toggle_rule($rule_id, $is_active);
                    echo '<div class="notice notice-success"><p>✅ Règle ' . ($is_active ? 'activée' : 'désactivée') . ' !</p></div>';
                    break;

                case 'reset_rule':
                    check_admin_referer('colis224_reset_automation');
                    $rule_id = intval($_POST['rule_id']);
                    Colis224_Departures_Automation::reset_rule_execution($rule_id);
                    echo '<div class="notice notice-success"><p>✅ Date de dernière exécution réinitialisée !</p></div>';
                    break;

                case 'delete_rule':
                    check_admin_referer('colis224_delete_automation');
                    $rule_id = intval($_POST['rule_id']);
                    Colis224_Departures_Automation::delete_rule($rule_id);
                    echo '<div class="notice notice-success"><p>✅ Règle supprimée !</p></div>';
                    break;

                case 'save_rule':
                    check_admin_referer('colis224_save_automation');
                    
                    $rule_data = array(
                        'rule_name' => sanitize_text_field($_POST['rule_name']),
                        'departure_city' => sanitize_text_field($_POST['departure_city']),
                        'departure_country' => sanitize_text_field($_POST['departure_country']),
                        'departure_country_code' => sanitize_text_field($_POST['departure_country_code']),
                        'arrival_city' => sanitize_text_field($_POST['arrival_city']),
                        'arrival_country' => sanitize_text_field($_POST['arrival_country']),
                        'arrival_country_code' => sanitize_text_field($_POST['arrival_country_code']),
                        'transport_type' => sanitize_text_field($_POST['transport_type']),
                        'frequency' => sanitize_text_field($_POST['frequency']),
                        'days_of_week' => sanitize_text_field($_POST['days_of_week']),
                        'days_interval' => !empty($_POST['days_interval']) ? intval($_POST['days_interval']) : null,
                        'random_day_in_week' => isset($_POST['random_day_in_week']) ? 1 : 0,
                        'departure_time' => sanitize_text_field($_POST['departure_time']),
                        'estimated_duration' => sanitize_text_field($_POST['estimated_duration']),
                        'default_capacity' => intval($_POST['default_capacity']),
                        'default_price' => !empty($_POST['default_price']) ? floatval($_POST['default_price']) : null,
                        'currency' => sanitize_text_field($_POST['currency']),
                        'whatsapp_number' => sanitize_text_field($_POST['whatsapp_number']),
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    );

                    if (!empty($_POST['rule_id'])) {
                        Colis224_Departures_Automation::update_rule(intval($_POST['rule_id']), $rule_data);
                        echo '<div class="notice notice-success"><p>✅ Règle mise à jour !</p></div>';
                    } else {
                        Colis224_Departures_Automation::add_rule($rule_data);
                        echo '<div class="notice notice-success"><p>✅ Règle créée !</p></div>';
                    }
                    break;

                case 'run_manual':
                    check_admin_referer('colis224_manual_automation');
                    $count = Colis224_Departures_Automation::run_manual_automation();
                    echo '<div class="notice notice-success"><p>✅ Automatisation exécutée ! ' . $count . ' départ(s) créé(s).</p></div>';
                    break;
            }
        }

        // Récupérer les règles
        $rules = Colis224_Departures_Automation::get_all_rules();
        $countries = Colis224_Departures::get_countries();

        // Récupération pour l'édition
        $editing_rule = null;
        if (isset($_GET['edit'])) {
            $editing_rule = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}colis224_departure_automation_rules WHERE id = %d",
                intval($_GET['edit'])
            ));
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🤖 Automatisation des Départs</h1>
            <a href="<?php echo admin_url('admin.php?page=colis224-departures-automation'); ?>" class="page-title-action">➕ Nouvelle règle</a>
            <hr class="wp-header-end">

            <style>
                .colis224-automation-container {
                    max-width: 1400px;
                    margin: 20px 0;
                }
                .colis224-automation-grid {
                    display: grid;
                    grid-template-columns: 1fr 2fr;
                    gap: 20px;
                    margin-top: 20px;
                }
                .colis224-automation-card {
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    padding: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .colis224-automation-card h2 {
                    margin-top: 0;
                    padding-bottom: 15px;
                    border-bottom: 2px solid #667eea;
                }
                .rule-item {
                    background: #f9f9f9;
                    border: 1px solid #e0e0e0;
                    border-left: 4px solid #667eea;
                    padding: 15px;
                    margin-bottom: 15px;
                    border-radius: 4px;
                    transition: all 0.3s;
                }
                .rule-item:hover {
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .rule-item.inactive {
                    opacity: 0.6;
                    border-left-color: #ccc;
                }
                .rule-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }
                .rule-title {
                    font-weight: bold;
                    font-size: 16px;
                    color: #333;
                }
                .rule-status {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: bold;
                }
                .rule-status.active {
                    background: #d4edda;
                    color: #155724;
                }
                .rule-status.inactive {
                    background: #f8d7da;
                    color: #721c24;
                }
                .rule-details {
                    font-size: 14px;
                    color: #666;
                    margin-bottom: 10px;
                }
                .rule-details strong {
                    color: #333;
                }
                .rule-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: 12px;
                }
                .rule-actions button,
                .rule-actions a {
                    padding: 6px 12px;
                    font-size: 13px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn-toggle {
                    background: #667eea;
                    color: white;
                }
                .btn-toggle:hover {
                    background: #5568d3;
                }
                .btn-reset {
                    background: #ffc107;
                    color: #333;
                }
                .btn-reset:hover {
                    background: #e0a800;
                }
                .btn-edit {
                    background: #17a2b8;
                    color: white;
                }
                .btn-edit:hover {
                    background: #138496;
                }
                .btn-delete {
                    background: #dc3545;
                    color: white;
                }
                .btn-delete:hover {
                    background: #c82333;
                }
                .form-group {
                    margin-bottom: 15px;
                }
                .form-group label {
                    display: block;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .form-group input,
                .form-group select,
                .form-group textarea {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .form-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }
                .checkbox-group {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                .checkbox-group label {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    font-weight: normal;
                }
                .info-box {
                    background: #e7f3ff;
                    border-left: 4px solid #2196f3;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .info-box h3 {
                    margin-top: 0;
                    color: #1976d2;
                }
                .manual-run-box {
                    background: #fff3cd;
                    border-left: 4px solid #ffc107;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
            </style>

            <div class="colis224-automation-container">
                
                <!-- Info Box -->
                <div class="info-box">
                    <h3>ℹ️ Comment fonctionne l'automatisation ?</h3>
                    <p><strong>Le système crée automatiquement des départs selon les règles que vous définissez.</strong></p>
                    <ul>
                        <li>📅 <strong>Quotidien</strong> : Un départ est créé chaque jour</li>
                        <li>📆 <strong>Hebdomadaire</strong> : Départs créés les jours spécifiques de la semaine</li>
                        <li>🔄 <strong>Intervalle</strong> : Départs créés tous les X jours</li>
                        <li>🎲 <strong>Aléatoire</strong> : Un seul départ créé dans la semaine, jour choisi aléatoirement</li>
                    </ul>
                    <p><em>Les départs sont programmés 7 jours à l'avance pour donner le temps aux clients de réserver.</em></p>
                </div>

                <!-- Manual Run Box -->
                <div class="manual-run-box">
                    <h3>⚡ Test manuel de l'automatisation</h3>
                    <p>Cliquez sur le bouton ci-dessous pour exécuter immédiatement l'automatisation (utile pour tester).</p>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('colis224_manual_automation'); ?>
                        <input type="hidden" name="action" value="run_manual">
                        <button type="submit" class="button button-primary">▶️ Exécuter maintenant</button>
                    </form>
                </div>

                <div class="colis224-automation-grid">
                    
                    <!-- Formulaire de création/édition -->
                    <div class="colis224-automation-card">
                        <h2><?php echo $editing_rule ? '✏️ Modifier la règle' : '➕ Nouvelle règle'; ?></h2>
                        
                        <form method="post">
                            <?php wp_nonce_field('colis224_save_automation'); ?>
                            <input type="hidden" name="action" value="save_rule">
                            <?php if ($editing_rule): ?>
                                <input type="hidden" name="rule_id" value="<?php echo $editing_rule->id; ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label>Nom de la règle *</label>
                                <input type="text" name="rule_name" required
                                       value="<?php echo $editing_rule ? esc_attr($editing_rule->rule_name) : ''; ?>"
                                       placeholder="Ex: Conakry → Paris (Tous les 3 jours)">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ville de départ *</label>
                                    <input type="text" name="departure_city" required
                                           value="<?php echo $editing_rule ? esc_attr($editing_rule->departure_city) : 'Conakry'; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Pays de départ *</label>
                                    <select name="departure_country_code" required>
                                        <?php foreach ($countries as $code => $country): ?>
                                            <option value="<?php echo esc_attr($code); ?>"
                                                <?php echo ($editing_rule && $editing_rule->departure_country_code == $code) ? 'selected' : ''; ?>>
                                                <?php echo $country['flag'] . ' ' . $country['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ville d'arrivée *</label>
                                    <input type="text" name="arrival_city" required
                                           value="<?php echo $editing_rule ? esc_attr($editing_rule->arrival_city) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Pays d'arrivée *</label>
                                    <select name="arrival_country_code" required>
                                        <?php foreach ($countries as $code => $country): ?>
                                            <option value="<?php echo esc_attr($code); ?>"
                                                <?php echo ($editing_rule && $editing_rule->arrival_country_code == $code) ? 'selected' : ''; ?>>
                                                <?php echo $country['flag'] . ' ' . $country['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" name="departure_country" value="Guinée">
                            <input type="hidden" name="arrival_country" value="France">

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Type de transport *</label>
                                    <select name="transport_type" required>
                                        <option value="plane" <?php echo ($editing_rule && $editing_rule->transport_type == 'plane') ? 'selected' : ''; ?>>
                                            ✈️ Avion
                                        </option>
                                        <option value="boat" <?php echo ($editing_rule && $editing_rule->transport_type == 'boat') ? 'selected' : ''; ?>>
                                            🚢 Bateau
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Fréquence *</label>
                                    <select name="frequency" id="frequency" required>
                                        <option value="daily" <?php echo ($editing_rule && $editing_rule->frequency == 'daily') ? 'selected' : ''; ?>>
                                            Quotidien
                                        </option>
                                        <option value="weekly" <?php echo ($editing_rule && $editing_rule->frequency == 'weekly') ? 'selected' : ''; ?>>
                                            Hebdomadaire
                                        </option>
                                        <option value="days_interval" <?php echo ($editing_rule && $editing_rule->frequency == 'days_interval') ? 'selected' : ''; ?>>
                                            Tous les X jours
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" id="days-of-week-group">
                                <label>Jours de la semaine</label>
                                <div class="checkbox-group">
                                    <?php
                                    $days = array(
                                        1 => 'Lundi',
                                        2 => 'Mardi',
                                        3 => 'Mercredi',
                                        4 => 'Jeudi',
                                        5 => 'Vendredi',
                                        6 => 'Samedi',
                                        7 => 'Dimanche'
                                    );
                                    $selected_days = $editing_rule ? explode(',', $editing_rule->days_of_week) : array();
                                    foreach ($days as $num => $name):
                                    ?>
                                        <label>
                                            <input type="checkbox" name="days_of_week[]" value="<?php echo $num; ?>"
                                                <?php echo in_array($num, $selected_days) ? 'checked' : ''; ?>>
                                            <?php echo $name; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="random_day_in_week" value="1"
                                        <?php echo ($editing_rule && $editing_rule->random_day_in_week) ? 'checked' : ''; ?>>
                                    Jour aléatoire dans la semaine (1 seul départ/semaine)
                                </label>
                            </div>

                            <div class="form-group" id="days-interval-group" style="display: none;">
                                <label>Intervalle de jours</label>
                                <input type="number" name="days_interval" min="1" max="30"
                                       value="<?php echo $editing_rule ? esc_attr($editing_rule->days_interval) : '3'; ?>">
                                <small>Créer un départ tous les X jours</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Heure de départ *</label>
                                    <input type="time" name="departure_time" required
                                           value="<?php echo $editing_rule ? esc_attr($editing_rule->departure_time) : '08:00'; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Durée estimée</label>
                                    <input type="text" name="estimated_duration"
                                           value="<?php echo $editing_rule ? esc_attr($editing_rule->estimated_duration) : ''; ?>"
                                           placeholder="Ex: 6h, 2h30">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Capacité par défaut (kg) *</label>
                                    <input type="number" name="default_capacity" required
                                           value="<?php echo $editing_rule ? esc_attr($editing_rule->default_capacity) : '100'; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Prix par défaut</label>
                                    <input type="number" name="default_price" step="0.01"
                                           value="<?php echo $editing_rule ? esc_attr($editing_rule->default_price) : ''; ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Devise *</label>
                                    <select name="currency" required>
                                        <option value="GNF" <?php echo ($editing_rule && $editing_rule->currency == 'GNF') ? 'selected' : ''; ?>>GNF</option>
                                        <option value="EUR" <?php echo ($editing_rule && $editing_rule->currency == 'EUR') ? 'selected' : ''; ?>>EUR</option>
                                        <option value="USD" <?php echo ($editing_rule && $editing_rule->currency == 'USD') ? 'selected' : ''; ?>>USD</option>
                                        <option value="XOF" <?php echo ($editing_rule && $editing_rule->currency == 'XOF') ? 'selected' : ''; ?>>CFA</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Numéro WhatsApp</label>
                                    <input type="text" name="whatsapp_number"
                                           value="<?php echo $editing_rule ? esc_attr($editing_rule->whatsapp_number) : '+224620178930'; ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" value="1"
                                        <?php echo (!$editing_rule || $editing_rule->is_active) ? 'checked' : ''; ?>>
                                    Règle active
                                </label>
                            </div>

                            <button type="submit" class="button button-primary">💾 Enregistrer la règle</button>
                            <?php if ($editing_rule): ?>
                                <a href="<?php echo admin_url('admin.php?page=colis224-departures-automation'); ?>" class="button">Annuler</a>
                            <?php endif; ?>
                        </form>

                        <script>
                        jQuery(document).ready(function($) {
                            // Gestion de l'affichage conditionnel
                            function toggleFrequencyFields() {
                                var frequency = $('#frequency').val();
                                if (frequency === 'days_interval') {
                                    $('#days-interval-group').show();
                                } else {
                                    $('#days-interval-group').hide();
                                }
                            }
                            
                            $('#frequency').on('change', toggleFrequencyFields);
                            toggleFrequencyFields();

                            // Convertir les checkboxes en string comma-separated
                            $('form').on('submit', function() {
                                var checkedDays = [];
                                $('input[name="days_of_week[]"]:checked').each(function() {
                                    checkedDays.push($(this).val());
                                });
                                
                                // Créer un input hidden avec la valeur
                                $('<input>').attr({
                                    type: 'hidden',
                                    name: 'days_of_week',
                                    value: checkedDays.join(',')
                                }).appendTo($(this));
                                
                                // Désactiver les checkboxes pour éviter qu'elles ne soient envoyées
                                $('input[name="days_of_week[]"]').prop('disabled', true);

                                // Définir le pays basé sur le code
                                var departureCode = $('select[name="departure_country_code"]').val();
                                var arrivalCode = $('select[name="arrival_country_code"]').val();
                                $('input[name="departure_country"]').val($('select[name="departure_country_code"] option:selected').text().split(' ').slice(1).join(' '));
                                $('input[name="arrival_country"]').val($('select[name="arrival_country_code"] option:selected').text().split(' ').slice(1).join(' '));
                            });
                        });
                        </script>
                    </div>

                    <!-- Liste des règles -->
                    <div class="colis224-automation-card">
                        <h2>📋 Règles d'automatisation</h2>
                        
                        <?php if (empty($rules)): ?>
                            <p><em>Aucune règle d'automatisation configurée.</em></p>
                        <?php else: ?>
                            <?php foreach ($rules as $rule): ?>
                                <div class="rule-item <?php echo $rule->is_active ? '' : 'inactive'; ?>">
                                    <div class="rule-header">
                                        <div class="rule-title"><?php echo esc_html($rule->rule_name); ?></div>
                                        <span class="rule-status <?php echo $rule->is_active ? 'active' : 'inactive'; ?>">
                                            <?php echo $rule->is_active ? '✅ Active' : '⏸️ Inactive'; ?>
                                        </span>
                                    </div>

                                    <div class="rule-details">
                                        <div><strong>Route:</strong> <?php echo esc_html($rule->departure_city); ?> → <?php echo esc_html($rule->arrival_city); ?></div>
                                        <div><strong>Type:</strong> <?php echo $rule->transport_type == 'plane' ? '✈️ Avion' : '🚢 Bateau'; ?></div>
                                        <div><strong>Fréquence:</strong> 
                                            <?php 
                                            switch ($rule->frequency) {
                                                case 'daily':
                                                    echo 'Quotidien';
                                                    break;
                                                case 'weekly':
                                                    if ($rule->random_day_in_week) {
                                                        echo 'Hebdomadaire (jour aléatoire)';
                                                    } else {
                                                        $days_names = array(1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim');
                                                        $selected = explode(',', $rule->days_of_week);
                                                        $day_labels = array();
                                                        foreach ($selected as $d) {
                                                            $day_labels[] = $days_names[$d];
                                                        }
                                                        echo 'Hebdomadaire (' . implode(', ', $day_labels) . ')';
                                                    }
                                                    break;
                                                case 'days_interval':
                                                    echo 'Tous les ' . $rule->days_interval . ' jours';
                                                    break;
                                            }
                                            ?>
                                        </div>
                                        <div><strong>Heure:</strong> <?php echo date('H:i', strtotime($rule->departure_time)); ?></div>
                                        <div><strong>Capacité:</strong> <?php echo $rule->default_capacity; ?> kg</div>
                                        <?php if ($rule->last_execution): ?>
                                            <div><strong>Dernière exécution:</strong> <?php echo date('d/m/Y H:i', strtotime($rule->last_execution)); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="rule-actions">
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('colis224_toggle_automation'); ?>
                                            <input type="hidden" name="action" value="toggle_rule">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule->id; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $rule->is_active ? 0 : 1; ?>">
                                            <button type="submit" class="btn-toggle">
                                                <?php echo $rule->is_active ? '⏸️ Désactiver' : '▶️ Activer'; ?>
                                            </button>
                                        </form>

                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('colis224_reset_automation'); ?>
                                            <input type="hidden" name="action" value="reset_rule">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule->id; ?>">
                                            <button type="submit" class="btn-reset" title="Réinitialiser la date d'exécution">
                                                🔄 Réinitialiser
                                            </button>
                                        </form>

                                        <a href="<?php echo admin_url('admin.php?page=colis224-departures-automation&edit=' . $rule->id); ?>" class="btn-edit">
                                            ✏️ Modifier
                                        </a>

                                        <form method="post" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette règle ?');">
                                            <?php wp_nonce_field('colis224_delete_automation'); ?>
                                            <input type="hidden" name="action" value="delete_rule">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule->id; ?>">
                                            <button type="submit" class="btn-delete">
                                                🗑️ Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
