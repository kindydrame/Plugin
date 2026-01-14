<?php
/**
 * Interface admin pour la migration doublons
 *
 * @package Colis224_Logistics
 * @subpackage Admin
 * @version 2.13.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Migration_Duplicates_Admin {

    /**
     * Afficher la page de migration
     */
    public static function display_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes.'));
        }

        // Traiter la migration
        if (isset($_POST['colis224_run_duplicates_migration'])) {
            if (check_admin_referer('colis224_duplicates_migration', 'migration_nonce')) {
                $result = Colis224_Migration_Duplicates::run();
                if ($result['success']) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                }
            }
        }

        // Traiter le rollback
        if (isset($_POST['colis224_rollback_duplicates_migration'])) {
            if (check_admin_referer('colis224_duplicates_rollback', 'rollback_nonce')) {
                $result = Colis224_Migration_Duplicates::rollback();
                if ($result['success']) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                }
            }
        }

        $status = Colis224_Migration_Duplicates::check_status();

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-database-import"></span>
                Migration: Détection des Doublons
            </h1>

            <!-- Statut -->
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
                        <td><strong>Table client_merges créée</strong></td>
                        <td><?php echo $status['table_exists'] ? '✅ Oui' : '❌ Non'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Fusions effectuées</strong></td>
                        <td><strong><?php echo number_format($status['merge_count']); ?></strong></td>
                    </tr>
                </table>
            </div>

            <!-- Actions -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>🔧 Actions</h2>

                <?php if (!$status['is_migrated']): ?>
                    <form method="post">
                        <?php wp_nonce_field('colis224_duplicates_migration', 'migration_nonce'); ?>
                        <button type="submit" name="colis224_run_duplicates_migration" class="button button-primary button-large">
                            <span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span>
                            Exécuter la Migration
                        </button>
                    </form>
                <?php else: ?>
                    <p style="color: #666; margin-bottom: 15px;">
                        La migration a été effectuée. Vous pouvez maintenant utiliser la détection des doublons.
                    </p>

                    <a href="?page=colis224-duplicates" class="button button-primary">
                        <span class="dashicons dashicons-groups" style="vertical-align: middle;"></span>
                        Accéder à la Détection des Doublons
                    </a>

                    <form method="post" style="display: inline-block; margin-left: 10px;" onsubmit="return confirm('⚠️ Voulez-vous vraiment effectuer un rollback ?\n\nNote: La table client_merges ne sera pas supprimée par sécurité.');">
                        <?php wp_nonce_field('colis224_duplicates_rollback', 'rollback_nonce'); ?>
                        <button type="submit" name="colis224_rollback_duplicates_migration" class="button button-secondary">
                            <span class="dashicons dashicons-backup" style="vertical-align: middle;"></span>
                            Rollback
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Documentation -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📖 Documentation</h2>

                <h3>Que fait cette migration ?</h3>
                <ol>
                    <li><strong>Création de la table client_merges</strong> : Table pour l'historique des fusions</li>
                </ol>

                <h3>Système de Détection Intelligent</h3>
                <p>L'algorithme de détection utilise plusieurs critères :</p>
                <ul>
                    <li>✅ <strong>Similarité du nom</strong> (poids 50%) - Algorithme Levenshtein avec normalisation</li>
                    <li>✅ <strong>Similarité du téléphone</strong> (poids 40%) - Normalisation internationale + comparaison des 8 derniers chiffres</li>
                    <li>✅ <strong>Similarité de l'email</strong> (poids 10%) - Si renseigné</li>
                </ul>

                <p>
                    <strong>Seuil de détection :</strong> <?php echo Colis224_Duplicate_Detector::SIMILARITY_THRESHOLD; ?>%
                    <br>Les clients avec un score ≥ <?php echo Colis224_Duplicate_Detector::SIMILARITY_THRESHOLD; ?>% sont considérés comme des doublons potentiels.
                </p>

                <h3>Normalisation des Données</h3>
                <ul>
                    <li>🔤 <strong>Noms</strong> : Minuscules, suppression accents, caractères spéciaux</li>
                    <li>📞 <strong>Téléphones</strong> : Suppression espaces, tirets, parenthèses, préfixes pays</li>
                    <li>✉️ <strong>Emails</strong> : Minuscules, comparaison exacte</li>
                </ul>

                <h3>Processus de Fusion</h3>
                <ol>
                    <li>Sélection du client principal à conserver</li>
                    <li>Transfert de tous les colis vers le client principal</li>
                    <li>Transfert des points de fidélité</li>
                    <li>Enregistrement dans l'historique (client_merges)</li>
                    <li>Désactivation des doublons (soft delete)</li>
                </ol>

                <h3>Sécurité</h3>
                <ul>
                    <li>🔒 Transactions SQL (COMMIT/ROLLBACK)</li>
                    <li>🔒 Historique complet des fusions</li>
                    <li>🔒 Soft delete (clients désactivés mais conservés)</li>
                    <li>🔒 Confirmation obligatoire avant fusion</li>
                </ul>

                <h3>Prévention des Doublons</h3>
                <p>
                    Lors de la création d'un nouveau client, le système vérifie automatiquement s'il existe
                    un client similaire et affiche un avertissement.
                </p>
            </div>

            <!-- Inspection technique -->
            <?php if ($status['table_exists']): ?>
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>🔍 Inspection Technique</h2>

                <h3>Structure de la table client_merges</h3>
                <?php
                global $wpdb;
                $table_merges = $wpdb->prefix . 'colis224_client_merges';
                $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_merges");
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
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
