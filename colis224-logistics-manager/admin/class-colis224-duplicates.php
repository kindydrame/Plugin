<?php
/**
 * Interface de gestion des clients dupliqués
 *
 * @package Colis224_Logistics
 * @subpackage Admin
 * @version 2.13.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Duplicates {

    /**
     * Afficher la page
     */
    public static function display_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes.'));
        }

        // Traiter la fusion
        if (isset($_POST['action']) && $_POST['action'] === 'merge_clients') {
            self::handle_merge();
        }

        // Déterminer la vue
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'duplicates';

        if ($view === 'history') {
            self::display_history();
        } else {
            self::display_duplicates();
        }
    }

    /**
     * Afficher les doublons détectés
     */
    private static function display_duplicates() {
        // Détecter les doublons
        $duplicate_groups = Colis224_Duplicate_Detector::detect_duplicates(30);

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                Détection des Clients Dupliqués
            </h1>

            <!-- Navigation -->
            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="?page=colis224-duplicates&view=duplicates" class="nav-tab nav-tab-active">
                    🔍 Doublons Détectés
                </a>
                <a href="?page=colis224-duplicates&view=history" class="nav-tab">
                    📜 Historique des Fusions
                </a>
            </nav>

            <!-- Statistiques -->
            <div class="colis224-card">
                <div class="colis224-dashboard-grid colis224-grid-4">
                    <div class="colis224-card colis224-card-orange">
                        <h4><span class="dashicons dashicons-warning"></span> Groupes de Doublons</h4>
                        <p class="colis224-big-number"><?php echo count($duplicate_groups); ?></p>
                    </div>
                    <div class="colis224-card colis224-card-blue">
                        <h4><span class="dashicons dashicons-admin-users"></span> Clients Concernés</h4>
                        <p class="colis224-big-number">
                            <?php
                            $total_clients = 0;
                            foreach ($duplicate_groups as $group) {
                                $total_clients += $group['count'];
                            }
                            echo $total_clients;
                            ?>
                        </p>
                    </div>
                    <div class="colis224-card colis224-card-green">
                        <h4><span class="dashicons dashicons-yes-alt"></span> Seuil de Similarité</h4>
                        <p class="colis224-big-number"><?php echo Colis224_Duplicate_Detector::SIMILARITY_THRESHOLD; ?>%</p>
                    </div>
                    <div class="colis224-card colis224-card-purple">
                        <h4><span class="dashicons dashicons-backup"></span> Fusions Totales</h4>
                        <p class="colis224-big-number">
                            <?php
                            global $wpdb;
                            $table_merges = $wpdb->prefix . 'colis224_client_merges';
                            $merge_count = $wpdb->get_var("SELECT COUNT(DISTINCT master_client_id) FROM $table_merges");
                            echo intval($merge_count);
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Liste des doublons -->
            <?php if (empty($duplicate_groups)): ?>
                <div class="colis224-card" style="margin-top: 20px;">
                    <div style="text-align: center; padding: 40px;">
                        <span class="dashicons dashicons-yes-alt" style="font-size: 64px; color: #46b450; opacity: 0.5;"></span>
                        <h2 style="color: #46b450; margin-top: 20px;">✅ Aucun Doublon Détecté</h2>
                        <p style="color: #666; font-size: 16px;">
                            Votre base de données clients est propre. Aucun client en doublon n'a été trouvé.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($duplicate_groups as $index => $group): ?>
                    <div class="colis224-card" style="margin-top: 20px; border-left: 4px solid #f0a000;">
                        <h3 style="margin: 0 0 15px 0;">
                            🔍 Groupe #<?php echo ($index + 1); ?>
                            <span style="background: #f0a000; color: white; padding: 5px 10px; border-radius: 3px; font-size: 14px; margin-left: 10px;">
                                <?php echo $group['count']; ?> clients similaires
                            </span>
                            <span style="background: #2271b1; color: white; padding: 5px 10px; border-radius: 3px; font-size: 14px; margin-left: 5px;">
                                Similarité: <?php echo round($group['similarity'], 1); ?>%
                            </span>
                        </h3>

                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th style="width: 30px;"></th>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th>Adresse</th>
                                    <th>Colis</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                global $wpdb;
                                $table_parcels = $wpdb->prefix . 'colis224_parcels';

                                foreach ($group['clients'] as $client):
                                    $parcel_count = $wpdb->get_var($wpdb->prepare(
                                        "SELECT COUNT(*) FROM $table_parcels WHERE client_id = %d",
                                        $client->id
                                    ));
                                ?>
                                    <tr>
                                        <td>
                                            <input type="radio" name="master_client_<?php echo $index; ?>" value="<?php echo $client->id; ?>">
                                        </td>
                                        <td><strong><?php echo $client->id; ?></strong></td>
                                        <td><?php echo esc_html($client->name); ?></td>
                                        <td><?php echo esc_html($client->phone); ?></td>
                                        <td><?php echo esc_html($client->email); ?></td>
                                        <td><?php echo esc_html($client->address); ?></td>
                                        <td><strong><?php echo $parcel_count; ?></strong> colis</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="margin-top: 15px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                            <form method="post" onsubmit="return confirm('⚠️ ATTENTION !\n\nCette action va fusionner les clients sélectionnés.\n\nLes colis et points de fidélité seront transférés vers le client principal.\nLes doublons seront désactivés.\n\nCette action est IRRÉVERSIBLE.\n\nVoulez-vous continuer ?');">
                                <input type="hidden" name="action" value="merge_clients">
                                <?php wp_nonce_field('colis224_merge_clients', 'merge_nonce'); ?>

                                <p style="margin: 0 0 10px 0; font-weight: bold;">
                                    <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                                    Sélectionnez le client principal à conserver (radio), puis cliquez sur "Fusionner"
                                </p>

                                <?php foreach ($group['clients'] as $client): ?>
                                    <input type="hidden" name="group_ids[]" value="<?php echo $client->id; ?>">
                                <?php endforeach; ?>

                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-image-rotate" style="vertical-align: middle;"></span>
                                    Fusionner ce Groupe
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Guide -->
            <div class="colis224-card" style="margin-top: 20px; background: #e7f5fe; border-left: 4px solid #2271b1;">
                <h3 style="margin: 0 0 10px 0; color: #2271b1;">
                    <span class="dashicons dashicons-info"></span>
                    Comment fusionner des clients ?
                </h3>
                <ol style="margin: 0; padding-left: 20px; color: #1d2327;">
                    <li><strong>Vérifiez</strong> que les clients dans le groupe sont réellement des doublons</li>
                    <li><strong>Sélectionnez</strong> le client principal en cliquant sur le bouton radio</li>
                    <li><strong>Cliquez</strong> sur "Fusionner ce Groupe"</li>
                    <li><strong>Confirmez</strong> l'action</li>
                </ol>
                <p style="margin: 10px 0 0 0; color: #666;">
                    ⚠️ Le client principal conservera ses informations. Les colis et points de fidélité des doublons lui seront transférés.
                    Les clients dupliqués seront désactivés mais conservés dans la base pour traçabilité.
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Afficher l'historique des fusions
     */
    private static function display_history() {
        global $wpdb;
        $table_merges = $wpdb->prefix . 'colis224_client_merges';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $merges = $wpdb->get_results("
            SELECT m.*, c.name as master_name
            FROM $table_merges m
            LEFT JOIN $table_clients c ON m.master_client_id = c.id
            ORDER BY m.merged_at DESC
            LIMIT 100
        ");

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                Détection des Clients Dupliqués
            </h1>

            <!-- Navigation -->
            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="?page=colis224-duplicates&view=duplicates" class="nav-tab">
                    🔍 Doublons Détectés
                </a>
                <a href="?page=colis224-duplicates&view=history" class="nav-tab nav-tab-active">
                    📜 Historique des Fusions
                </a>
            </nav>

            <!-- Historique -->
            <div class="colis224-card">
                <h2>📜 Historique des Fusions (<?php echo count($merges); ?>)</h2>

                <?php if (empty($merges)): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">
                        Aucune fusion n'a encore été effectuée.
                    </p>
                <?php else: ?>
                    <table class="widefat" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client Principal</th>
                                <th>Client Fusionné (ID)</th>
                                <th>Données Fusionnées</th>
                                <th>Fusionné par</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($merges as $merge):
                                $merged_data = json_decode($merge->merged_client_data);
                                $user = get_userdata($merge->merged_by);
                            ?>
                                <tr>
                                    <td><?php echo mysql2date('d/m/Y H:i', $merge->merged_at); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($merge->master_name); ?></strong>
                                        <br><small>ID: <?php echo $merge->master_client_id; ?></small>
                                    </td>
                                    <td>
                                        <strong>ID: <?php echo $merge->merged_client_id; ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($merged_data): ?>
                                            <strong><?php echo esc_html($merged_data->name); ?></strong><br>
                                            <small>
                                                📞 <?php echo esc_html($merged_data->phone); ?><br>
                                                <?php if (!empty($merged_data->email)): ?>
                                                    ✉️ <?php echo esc_html($merged_data->email); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user ? esc_html($user->display_name) : 'Inconnu'; ?></td>
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
     * Traiter la fusion
     */
    private static function handle_merge() {
        if (!check_admin_referer('colis224_merge_clients', 'merge_nonce')) {
            wp_die('Erreur de sécurité');
        }

        if (empty($_POST['group_ids']) || !is_array($_POST['group_ids'])) {
            echo '<div class="notice notice-error"><p>❌ Aucun client sélectionné.</p></div>';
            return;
        }

        // Trouver le master (celui avec le radio sélectionné)
        $master_id = null;
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'master_client_') === 0) {
                $master_id = intval($value);
                break;
            }
        }

        if (!$master_id) {
            echo '<div class="notice notice-error"><p>❌ Veuillez sélectionner le client principal.</p></div>';
            return;
        }

        $group_ids = array_map('intval', $_POST['group_ids']);
        $duplicate_ids = array_diff($group_ids, array($master_id));

        if (empty($duplicate_ids)) {
            echo '<div class="notice notice-error"><p>❌ Aucun doublon à fusionner.</p></div>';
            return;
        }

        // Effectuer la fusion
        $result = Colis224_Duplicate_Detector::merge_clients($master_id, $duplicate_ids);

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
}
