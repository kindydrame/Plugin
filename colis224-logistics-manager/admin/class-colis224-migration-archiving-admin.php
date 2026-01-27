<?php
/**
 * Interface admin pour la migration d'archivage
 *
 * @package Colis224_Logistics
 * @subpackage Admin
 * @version 2.12.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Migration_Archiving_Admin {

    /**
     * Afficher la page de migration
     */
    public static function display_page() {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
        }

        // Traiter l'action de migration
        if (isset($_POST['colis224_run_archiving_migration'])) {
            if (check_admin_referer('colis224_archiving_migration', 'migration_nonce')) {
                $result = Colis224_Migration_Archiving::run();
                if ($result['success']) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                }
            }
        }

        // Traiter le rollback
        if (isset($_POST['colis224_rollback_archiving_migration'])) {
            if (check_admin_referer('colis224_archiving_rollback', 'rollback_nonce')) {
                $result = Colis224_Migration_Archiving::rollback();
                if ($result['success']) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                }
            }
        }

        // Obtenir le statut
        $status = Colis224_Migration_Archiving::check_status();

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-database-import"></span>
                Migration: Système d'Archivage
            </h1>

            <!-- Statut de la migration -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📊 Statut de la Migration</h2>

                <?php if ($status['is_migrated']): ?>
                    <div style="background: #d1f4e0; border: 1px solid #46b450; padding: 20px; border-radius: 5px; margin-top: 15px;">
                        <p style="margin: 0; font-size: 16px; color: #0a5d2a;">
                            ✅ <strong>Migration effectuée avec succès !</strong>
                        </p>
                        <p style="margin: 10px 0 0 0; color: #666;">
                            Version actuelle: <strong><?php echo esc_html($status['version']); ?></strong>
                        </p>
                    </div>
                <?php else: ?>
                    <div style="background: #fff3cd; border: 1px solid #f0a000; padding: 20px; border-radius: 5px; margin-top: 15px;">
                        <p style="margin: 0; font-size: 16px; color: #856404;">
                            ⚠️ <strong>Migration non effectuée</strong>
                        </p>
                        <p style="margin: 10px 0 0 0; color: #666;">
                            Cliquez sur le bouton ci-dessous pour exécuter la migration.
                        </p>
                    </div>
                <?php endif; ?>

                <table class="widefat" style="margin-top: 20px;">
                    <tr>
                        <td><strong>Version cible</strong></td>
                        <td><?php echo esc_html($status['target_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Colonnes d'archivage ajoutées</strong></td>
                        <td><?php echo $status['has_columns'] ? '✅ Oui' : '❌ Non'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Table archived_parcels créée</strong></td>
                        <td><?php echo $status['table_exists'] ? '✅ Oui' : '❌ Non'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Colis archivés</strong></td>
                        <td><strong><?php echo number_format($status['archived_count']); ?></strong></td>
                    </tr>
                </table>
            </div>

            <!-- Actions -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>🔧 Actions</h2>

                <?php if (!$status['is_migrated']): ?>
                    <form method="post">
                        <?php wp_nonce_field('colis224_archiving_migration', 'migration_nonce'); ?>
                        <button type="submit" name="colis224_run_archiving_migration" class="button button-primary button-large">
                            <span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span>
                            Exécuter la Migration
                        </button>
                    </form>
                <?php else: ?>
                    <p style="color: #666; margin-bottom: 15px;">
                        La migration a déjà été effectuée. Si vous rencontrez des problèmes, vous pouvez effectuer un rollback.
                    </p>

                    <form method="post" onsubmit="return confirm('⚠️ ATTENTION !\n\nLe rollback va supprimer les colonnes d\'archivage de la table parcels.\n\nLa table archived_parcels ne sera PAS supprimée par sécurité.\n\nVoulez-vous vraiment continuer ?');">
                        <?php wp_nonce_field('colis224_archiving_rollback', 'rollback_nonce'); ?>
                        <button type="submit" name="colis224_rollback_archiving_migration" class="button button-secondary">
                            <span class="dashicons dashicons-backup" style="vertical-align: middle;"></span>
                            Rollback de la Migration
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Documentation -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📖 Documentation</h2>

                <h3>Que fait cette migration ?</h3>
                <ol>
                    <li><strong>Création de la table archived_parcels</strong> : Table pour stocker les colis archivés</li>
                    <li><strong>Ajout des colonnes d'archivage</strong> :
                        <ul>
                            <li><code>is_archived</code> : Indicateur d'archivage (0/1)</li>
                            <li><code>archived_at</code> : Date d'archivage</li>
                        </ul>
                    </li>
                    <li><strong>Création d'un backup</strong> : Sauvegarde de la table parcels avant modification</li>
                </ol>

                <h3>Archivage Automatique</h3>
                <p>
                    Une fois la migration effectuée, le système archivera automatiquement les colis livrés depuis plus de
                    <strong><?php echo Colis224_Archiving::AUTO_ARCHIVE_DAYS; ?> jours</strong>.
                </p>
                <p>L'archivage automatique s'exécute quotidiennement via WordPress Cron.</p>

                <h3>Fonctionnalités</h3>
                <ul>
                    <li>✅ Archivage automatique des colis livrés après X jours</li>
                    <li>✅ Archivage manuel via l'interface admin</li>
                    <li>✅ Restauration des colis archivés</li>
                    <li>✅ Statistiques d'archivage (total, revenus, par période)</li>
                    <li>✅ Historique complet dans parcel_history</li>
                    <li>✅ Filtres et recherche dans les archives</li>
                </ul>

                <h3>Sécurité</h3>
                <ul>
                    <li>🔒 Backup automatique avant migration</li>
                    <li>🔒 Transactions SQL pour garantir la cohérence</li>
                    <li>🔒 Rollback automatique en cas d'erreur</li>
                    <li>🔒 Permissions admin requises</li>
                </ul>
            </div>

            <!-- Détails techniques -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>🔍 Inspection Technique</h2>

                <h3>Structure de la table archived_parcels</h3>
                <?php
                global $wpdb;
                $table_archived = $wpdb->prefix . 'colis224_archived_parcels';

                if ($status['table_exists']):
                    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_archived");
                    ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Colonne</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Clé</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($columns as $column): ?>
                                <tr>
                                    <td><code><?php echo esc_html($column->Field); ?></code></td>
                                    <td><?php echo esc_html($column->Type); ?></td>
                                    <td><?php echo $column->Null === 'YES' ? 'Oui' : 'Non'; ?></td>
                                    <td><?php echo esc_html($column->Key); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #999;">La table n'existe pas encore. Exécutez la migration pour la créer.</p>
                <?php endif; ?>

                <h3 style="margin-top: 30px;">Colonnes ajoutées à colis224_parcels</h3>
                <?php if ($status['has_columns']): ?>
                    <?php
                    $table_parcels = $wpdb->prefix . 'colis224_parcels';
                    $archiving_columns = $wpdb->get_results("
                        SHOW COLUMNS FROM $table_parcels
                        WHERE Field IN ('is_archived', 'archived_at')
                    ");
                    ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Colonne</th>
                                <th>Type</th>
                                <th>Défaut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archiving_columns as $column): ?>
                                <tr>
                                    <td><code><?php echo esc_html($column->Field); ?></code></td>
                                    <td><?php echo esc_html($column->Type); ?></td>
                                    <td><?php echo esc_html($column->Default); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #999;">Les colonnes n'ont pas encore été ajoutées.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
