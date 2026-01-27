<?php
/**
 * Interface admin pour les migrations
 *
 * @package Colis224_Logistics
 * @subpackage Admin
 * @version 2.11.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Migration_Admin {

    /**
     * Afficher la page de migration
     */
    public static function display_page() {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
        }

        // Traiter l'action de migration
        $message = '';
        $message_type = '';

        if (isset($_POST['colis224_run_migration']) && check_admin_referer('colis224_migration_action', 'colis224_migration_nonce')) {
            $result = Colis224_Migration_Validation::run();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }

        if (isset($_POST['colis224_rollback_migration']) && check_admin_referer('colis224_migration_action', 'colis224_migration_nonce')) {
            $backup_key = sanitize_text_field($_POST['backup_key']);
            $result = Colis224_Migration_Validation::rollback($backup_key);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }

        // Obtenir le statut actuel
        $status = Colis224_Migration_Validation::check_status();

        ?>
        <div class="wrap colis224-wrap">
            <h1>
                <span class="dashicons dashicons-database-import"></span>
                Migration Système de Validation v2.11.0
            </h1>

            <?php if ($message): ?>
                <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>

            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📊 État de la Migration</h2>

                <table class="widefat" style="margin-top: 15px;">
                    <tr>
                        <td><strong>Statut</strong></td>
                        <td>
                            <?php if ($status['is_migrated']): ?>
                                <span style="color: #46b450; font-weight: bold;">✅ Migration effectuée</span>
                            <?php else: ?>
                                <span style="color: #dc3232; font-weight: bold;">⏳ Migration en attente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Version actuelle</strong></td>
                        <td><?php echo esc_html($status['current_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Version cible</strong></td>
                        <td><?php echo esc_html($status['target_version']); ?></td>
                    </tr>
                </table>

                <?php if (!$status['is_migrated']): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-top: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #856404;">⚠️ Attention</h3>
                        <p style="margin: 0;">
                            Cette migration va ajouter les colonnes suivantes à la table <code>colis224_parcels</code> :
                        </p>
                        <ul style="margin-left: 20px;">
                            <li><code>created_by</code> - ID de l'utilisateur qui a créé le colis</li>
                            <li><code>validated_by</code> - ID de l'administrateur qui a validé</li>
                            <li><code>validated_at</code> - Date de validation</li>
                            <li><code>validation_status</code> - Statut (pending, approved, rejected)</li>
                            <li><code>validation_notes</code> - Notes de validation/rejet</li>
                        </ul>
                        <p style="margin-top: 10px;">
                            Une nouvelle table <code>colis224_parcel_history</code> sera créée pour la traçabilité complète.
                        </p>
                        <p style="margin-top: 10px; font-weight: bold; color: #d63638;">
                            ⚠️ Une sauvegarde automatique sera effectuée avant la migration.
                        </p>
                    </div>

                    <form method="post" style="margin-top: 20px;">
                        <?php wp_nonce_field('colis224_migration_action', 'colis224_migration_nonce'); ?>
                        <button type="submit" name="colis224_run_migration" class="button button-primary button-large" style="margin-right: 10px;">
                            <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                            Exécuter la Migration
                        </button>
                        <p class="description">Cette opération est sécurisée et peut être annulée (rollback).</p>
                    </form>

                <?php else: ?>
                    <div style="background: #d1f4e0; border: 1px solid #46b450; padding: 15px; border-radius: 5px; margin-top: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #0a5d2a;">✅ Migration effectuée avec succès</h3>
                        <p style="margin: 0;">
                            Le système de validation hiérarchique est maintenant actif. Les fonctionnalités suivantes sont disponibles :
                        </p>
                        <ul style="margin-left: 20px;">
                            <li>✅ Traçabilité complète (qui a créé chaque colis)</li>
                            <li>✅ Workflow de validation agent → admin</li>
                            <li>✅ Historique des modifications</li>
                            <li>✅ Restrictions sur les modifications sensibles</li>
                        </ul>
                    </div>

                    <div style="background: #f0f0f1; padding: 15px; border-radius: 5px; margin-top: 20px;">
                        <h3 style="margin: 0 0 10px 0;">🔧 Rollback (Annulation)</h3>
                        <p>
                            Si vous rencontrez des problèmes, vous pouvez annuler cette migration.
                            <strong>⚠️ Attention :</strong> Cette action supprimera les colonnes ajoutées et la table d'historique.
                        </p>
                        <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette migration ? Toutes les données de validation seront perdues.');">
                            <?php wp_nonce_field('colis224_migration_action', 'colis224_migration_nonce'); ?>
                            <input type="hidden" name="backup_key" value="<?php echo esc_attr(get_option('colis224_migration_backup_key', '')); ?>">
                            <button type="submit" name="colis224_rollback_migration" class="button button-secondary">
                                <span class="dashicons dashicons-undo" style="vertical-align: middle;"></span>
                                Annuler la Migration (Rollback)
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📚 Documentation</h2>

                <h3>Nouvelles fonctionnalités v2.11.0</h3>

                <h4>1. Workflow de Validation</h4>
                <p>
                    Lorsqu'un agent crée un colis, celui-ci passe en statut <code>pending</code> (en attente de validation).
                    L'administrateur reçoit une notification et peut :
                </p>
                <ul>
                    <li>✅ <strong>Approuver</strong> : Le colis devient actif et visible partout</li>
                    <li>❌ <strong>Rejeter</strong> : Le colis est marqué comme rejeté avec un commentaire</li>
                </ul>

                <h4>2. Restrictions Agents</h4>
                <p>Les agents ne peuvent plus :</p>
                <ul>
                    <li>Modifier les montants après validation admin</li>
                    <li>Changer le statut de paiement sans validation</li>
                    <li>Voir les colis rejetés (seulement admin)</li>
                </ul>

                <h4>3. Historique Complet</h4>
                <p>
                    Toutes les modifications sont enregistrées dans <code>colis224_parcel_history</code> :
                </p>
                <ul>
                    <li>Qui a modifié (utilisateur, rôle, IP)</li>
                    <li>Quand (date/heure)</li>
                    <li>Quoi (champ modifié, ancienne/nouvelle valeur)</li>
                </ul>

                <h4>4. Traçabilité</h4>
                <p>Chaque colis contient maintenant :</p>
                <ul>
                    <li><code>created_by</code> : Agent qui a créé le colis</li>
                    <li><code>validated_by</code> : Admin qui a validé/rejeté</li>
                    <li><code>validated_at</code> : Date/heure de validation</li>
                </ul>
            </div>

            <div class="colis224-card" style="margin-top: 20px;">
                <h2>🔍 Vérification Technique</h2>

                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'colis224_parcels';
                $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
                $column_names = array_map(function($col) { return $col->Field; }, $columns);
                ?>

                <h3>Colonnes actuelles de la table <code>colis224_parcels</code></h3>
                <div style="background: #f0f0f1; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;">
                    <?php
                    $validation_columns = array('created_by', 'validated_by', 'validated_at', 'validation_status', 'validation_notes');
                    foreach ($columns as $column) {
                        $is_validation_col = in_array($column->Field, $validation_columns);
                        $style = $is_validation_col ? 'color: #46b450; font-weight: bold;' : '';
                        echo '<div style="' . esc_attr($style) . '">';
                        echo esc_html($column->Field) . ' (' . esc_html($column->Type) . ')';
                        if ($is_validation_col) {
                            echo ' ✅ <em>Nouvelle colonne v2.11.0</em>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>

                <?php
                $history_table = $wpdb->prefix . 'colis224_parcel_history';
                $history_exists = $wpdb->get_var("SHOW TABLES LIKE '$history_table'");
                ?>

                <h3 style="margin-top: 20px;">Table d'historique</h3>
                <div style="background: #f0f0f1; padding: 10px; border-radius: 5px;">
                    <?php if ($history_exists): ?>
                        <span style="color: #46b450; font-weight: bold;">✅ Table <code><?php echo esc_html($history_table); ?></code> existe</span>
                        <?php
                        $count = $wpdb->get_var("SELECT COUNT(*) FROM $history_table");
                        echo '<p style="margin-top: 10px;">Enregistrements : <strong>' . esc_html($count) . '</strong></p>';
                        ?>
                    <?php else: ?>
                        <span style="color: #dc3232; font-weight: bold;">❌ Table <code><?php echo esc_html($history_table); ?></code> n'existe pas encore</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
