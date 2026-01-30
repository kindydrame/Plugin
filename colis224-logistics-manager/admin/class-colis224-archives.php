<?php
/**
 * Interface de gestion des archives
 *
 * @package Colis224_Logistics
 * @subpackage Admin
 * @version 2.12.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Archives {

    /**
     * Afficher la page des archives
     */
    public static function display_page() {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
        }

        // Traiter les actions
        if (isset($_POST['action']) && $_POST['action'] === 'restore_parcel' && isset($_POST['parcel_id'])) {
            if (check_admin_referer('colis224_restore_parcel', 'restore_nonce')) {
                $result = Colis224_Archiving::restore_parcel(intval($_POST['parcel_id']));
                if ($result['success']) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                }
            }
        }

        // Traiter l'archivage manuel
        if (isset($_POST['action']) && $_POST['action'] === 'manual_archive' && isset($_POST['parcel_id'])) {
            if (check_admin_referer('colis224_manual_archive', 'archive_nonce')) {
                $result = Colis224_Archiving::archive_parcel(intval($_POST['parcel_id']), 'manual_archive');
                if ($result['success']) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
                }
            }
        }

        // Déterminer la vue
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'archived';

        if ($view === 'active') {
            self::display_active_parcels();
        } else {
            self::display_archived_parcels();
        }
    }

    /**
     * Afficher les colis archivés
     */
    private static function display_archived_parcels() {
        // Filtres
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        $filters = array(
            'search' => $search,
            'date_from' => $date_from,
            'date_to' => $date_to
        );

        // Récupérer les colis archivés
        $archived_parcels = Colis224_Archiving::get_archived_parcels($filters, 100, 0);

        // Statistiques
        $stats = Colis224_Archiving::get_statistics();

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-archive"></span>
                Gestion des Archives
            </h1>

            <!-- Navigation -->
            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="?page=colis224-archives&view=archived" class="nav-tab nav-tab-active">
                    📦 Colis Archivés
                </a>
                <a href="?page=colis224-archives&view=active" class="nav-tab">
                    🔄 Archivage Manuel
                </a>
            </nav>

            <!-- Statistiques -->
            <div class="colis224-dashboard-grid colis224-grid-4">
                <div class="colis224-card colis224-card-blue">
                    <h4><span class="dashicons dashicons-archive"></span> Total Archivés</h4>
                    <p class="colis224-big-number"><?php echo number_format($stats['total_archived']); ?></p>
                </div>
                <div class="colis224-card colis224-card-green">
                    <h4><span class="dashicons dashicons-money-alt"></span> Revenu Total</h4>
                    <p class="colis224-big-number"><?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?> GNF</p>
                </div>
                <div class="colis224-card colis224-card-purple">
                    <h4><span class="dashicons dashicons-calendar-alt"></span> Archivage Auto</h4>
                    <p class="colis224-big-number"><?php echo Colis224_Archiving::AUTO_ARCHIVE_DAYS; ?> jours</p>
                    <small>après livraison</small>
                </div>
                <div class="colis224-card colis224-card-orange">
                    <h4><span class="dashicons dashicons-clock"></span> Prochain Archivage</h4>
                    <p class="colis224-big-number" style="font-size: 14px;">
                        <?php
                        $next_run = wp_next_scheduled('colis224_auto_archive_cron');
                        echo $next_run ? date_i18n('d/m/Y H:i', $next_run) : 'Non planifié';
                        ?>
                    </p>
                </div>
            </div>

            <!-- Filtres -->
            <div class="colis224-card" style="margin-top: 20px;">
                <form method="get">
                    <input type="hidden" name="page" value="colis224-archives">
                    <input type="hidden" name="view" value="archived">

                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end;">
                        <div>
                            <label for="s">Recherche</label>
                            <input type="text" name="s" id="s" value="<?php echo esc_attr($search); ?>"
                                   placeholder="N° suivi, nom, téléphone..."
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <label for="date_from">Date début</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <label for="date_to">Date fin</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                                Filtrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Liste des archives -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📋 Colis Archivés (<?php echo count($archived_parcels); ?>)</h2>

                <?php if (empty($archived_parcels)): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <span class="dashicons dashicons-inbox" style="font-size: 64px; opacity: 0.3;"></span>
                        <p style="font-size: 16px; margin-top: 10px;">Aucun colis archivé trouvé.</p>
                    </div>
                <?php else: ?>
                    <table class="widefat" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>N° Suivi</th>
                                <th>Destinataire</th>
                                <th>Montant</th>
                                <th>Date Livraison</th>
                                <th>Date Archivage</th>
                                <th>Raison</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_parcels as $parcel): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                                    <td>
                                        <?php echo esc_html($parcel->recipient_name); ?>
                                        <?php if ($parcel->recipient_phone): ?>
                                            <br><small><?php echo esc_html($parcel->recipient_phone); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> GNF</strong></td>
                                    <td><?php echo $parcel->delivery_date ? mysql2date('d/m/Y', $parcel->delivery_date) : '-'; ?></td>
                                    <td><?php echo mysql2date('d/m/Y H:i', $parcel->archived_at); ?></td>
                                    <td>
                                        <?php
                                        $reasons = array(
                                            'auto_archive_delivered' => '🤖 Auto (Livré)',
                                            'manual_archive' => '👤 Manuel'
                                        );
                                        echo isset($reasons[$parcel->archive_reason]) ? $reasons[$parcel->archive_reason] : $parcel->archive_reason;
                                        ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="restore_parcel">
                                            <input type="hidden" name="parcel_id" value="<?php echo $parcel->original_parcel_id; ?>">
                                            <?php wp_nonce_field('colis224_restore_parcel', 'restore_nonce'); ?>
                                            <button type="submit" class="button button-small"
                                                    onclick="return confirm('Voulez-vous vraiment restaurer ce colis ?');">
                                                <span class="dashicons dashicons-backup" style="vertical-align: middle;"></span>
                                                Restaurer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Statistiques par période -->
            <?php if (!empty($stats['by_month'])): ?>
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📊 Statistiques par Mois (12 derniers mois)</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Nombre</th>
                            <th>Revenu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_month'] as $row): ?>
                            <tr>
                                <td><?php echo date_i18n('F Y', strtotime($row['month'] . '-01')); ?></td>
                                <td><?php echo number_format($row['count']); ?></td>
                                <td><strong><?php echo number_format($row['revenue'], 0, ',', ' '); ?> GNF</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Afficher les colis actifs (pour archivage manuel)
     */
    private static function display_active_parcels() {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Récupérer les colis livrés non archivés
        $active_parcels = $wpdb->get_results("
            SELECT *
            FROM $table_parcels
            WHERE status = 'Livré'
            AND is_archived = 0
            ORDER BY delivery_date DESC
            LIMIT 100
        ");

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-archive"></span>
                Gestion des Archives
            </h1>

            <!-- Navigation -->
            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="?page=colis224-archives&view=archived" class="nav-tab">
                    📦 Colis Archivés
                </a>
                <a href="?page=colis224-archives&view=active" class="nav-tab nav-tab-active">
                    🔄 Archivage Manuel
                </a>
            </nav>

            <!-- Liste des colis actifs -->
            <div class="colis224-card">
                <h2>📋 Colis Livrés - Archivage Manuel (<?php echo count($active_parcels); ?>)</h2>
                <p>Ces colis ont été livrés mais ne sont pas encore archivés. Vous pouvez les archiver manuellement.</p>

                <?php if (empty($active_parcels)): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <span class="dashicons dashicons-yes-alt" style="font-size: 64px; opacity: 0.3;"></span>
                        <p style="font-size: 16px; margin-top: 10px;">Aucun colis livré non archivé.</p>
                    </div>
                <?php else: ?>
                    <table class="widefat" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>N° Suivi</th>
                                <th>Destinataire</th>
                                <th>Montant</th>
                                <th>Date Livraison</th>
                                <th>Jours depuis livraison</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_parcels as $parcel): ?>
                                <?php
                                $days_since_delivery = 0;
                                if ($parcel->delivery_date) {
                                    $delivery = new DateTime($parcel->delivery_date);
                                    $now = new DateTime();
                                    $days_since_delivery = $now->diff($delivery)->days;
                                }
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                                    <td>
                                        <?php echo esc_html($parcel->recipient_name); ?>
                                        <?php if ($parcel->recipient_phone): ?>
                                            <br><small><?php echo esc_html($parcel->recipient_phone); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> GNF</strong></td>
                                    <td><?php echo $parcel->delivery_date ? mysql2date('d/m/Y', $parcel->delivery_date) : '-'; ?></td>
                                    <td>
                                        <span style="<?php echo $days_since_delivery >= Colis224_Archiving::AUTO_ARCHIVE_DAYS ? 'color: orange; font-weight: bold;' : ''; ?>">
                                            <?php echo $days_since_delivery; ?> jours
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="manual_archive">
                                            <input type="hidden" name="parcel_id" value="<?php echo $parcel->id; ?>">
                                            <?php wp_nonce_field('colis224_manual_archive', 'archive_nonce'); ?>
                                            <button type="submit" class="button button-primary button-small"
                                                    onclick="return confirm('Voulez-vous vraiment archiver ce colis ?');">
                                                <span class="dashicons dashicons-archive" style="vertical-align: middle;"></span>
                                                Archiver
                                            </button>
                                        </form>
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
}
