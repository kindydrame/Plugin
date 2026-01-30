<?php
/**
 * Tableau de bord central
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Dashboard {

    public static function display_dashboard() {
        global $wpdb;

        // Déterminer le rôle de l'utilisateur
        $user_role = Colis224_Permissions::get_user_colis224_role();
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $is_agent = ($user_role === 'colis224_agent');
        $is_driver = ($user_role === 'colis224_driver');
        $is_accountant = ($user_role === 'colis224_accountant');

        // Récupération des statistiques (adaptées selon le rôle)
        $stats = self::get_statistics($user_role, $current_user_id);

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-airplane"></span>
                Tableau de Bord Colis224
            </h1>

            <?php if ($is_agent): ?>
                <!-- Dashboard Agent -->
                <div class="colis224-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 20px; padding: 20px;">
                    <h2 style="color: white; margin: 0 0 10px 0;">
                        <span class="dashicons dashicons-businessman" style="vertical-align: middle;"></span>
                        Tableau de Bord Agent
                    </h2>
                    <p style="margin: 0; opacity: 0.9;">Bienvenue, <?php echo esc_html(wp_get_current_user()->display_name); ?> !</p>
                </div>
                
                <?php 
                // DIAGNOSTIC DÉTAILLÉ POUR L'AGENT
                $table_parcels_check = $wpdb->prefix . 'colis224_parcels';
                $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels_check LIKE 'created_by'");
                $column_exists = !empty($columns);
                
                // Informations de diagnostic
                $current_user = wp_get_current_user();
                $total_parcels_all = $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels_check");
                $parcels_with_created_by = 0;
                $parcels_by_this_agent = 0;
                
                if ($column_exists) {
                    $parcels_with_created_by = $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels_check WHERE created_by IS NOT NULL");
                    $parcels_by_this_agent = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_parcels_check WHERE created_by = %d",
                        $current_user_id
                    ));
                }
                
                // Afficher le panneau de diagnostic
                ?>
                <div class="colis224-card" style="background: #f9f9f9; border-left: 5px solid #0073aa; margin-bottom: 20px; padding: 20px;">
                    <h3 style="margin: 0 0 15px 0; color: #0073aa;">
                        <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                        Diagnostic du Tableau de Bord Agent
                    </h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; font-weight: bold; width: 40%;">Utilisateur connecté :</td>
                            <td style="padding: 8px;"><?php echo esc_html($current_user->display_name); ?> (ID: <?php echo $current_user_id; ?>)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; font-weight: bold;">Rôle détecté :</td>
                            <td style="padding: 8px;">
                                <code><?php echo esc_html($user_role ?: 'Aucun rôle Colis224'); ?></code>
                                <?php if ($user_role === 'colis224_agent'): ?>
                                    <span style="color: #46b450;">✓ Correct</span>
                                <?php else: ?>
                                    <span style="color: #dc3232;">✗ Problème : Le rôle n'est pas détecté comme agent</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; font-weight: bold;">Colonne created_by :</td>
                            <td style="padding: 8px;">
                                <?php if ($column_exists): ?>
                                    <span style="color: #46b450;">✓ Existe</span>
                                <?php else: ?>
                                    <span style="color: #dc3232;">✗ N'existe pas - Migration requise</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; font-weight: bold;">Total colis dans la base :</td>
                            <td style="padding: 8px;"><?php echo number_format($total_parcels_all); ?></td>
                        </tr>
                        <?php if ($column_exists): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; font-weight: bold;">Colis avec created_by défini :</td>
                            <td style="padding: 8px;"><?php echo number_format($parcels_with_created_by); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; font-weight: bold;">Colis créés par vous :</td>
                            <td style="padding: 8px;">
                                <strong style="color: <?php echo $parcels_by_this_agent > 0 ? '#46b450' : '#dc3232'; ?>;">
                                    <?php echo number_format($parcels_by_this_agent); ?>
                                </strong>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td style="padding: 8px; font-weight: bold;">Statistiques affichées :</td>
                            <td style="padding: 8px;">
                                <strong><?php echo number_format($stats['parcels_total']); ?></strong> colis
                            </td>
                        </tr>
                    </table>
                    
                    <?php if (!$column_exists): ?>
                        <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 5px;">
                            <p style="margin: 0 0 10px 0; color: #856404; font-weight: bold;">
                                ⚠️ Action requise : Exécuter la migration
                            </p>
                            <p style="margin: 0; color: #856404;">
                                La colonne <code>created_by</code> n'existe pas encore. 
                                Allez dans <strong>Colis224 → 🔄 Migration</strong> et exécutez la migration de validation.
                            </p>
                        </div>
                    <?php elseif ($parcels_by_this_agent == 0 && $parcels_with_created_by > 0): ?>
                        <div style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
                            <p style="margin: 0 0 10px 0; color: #0d47a1; font-weight: bold;">
                                ℹ️ Information
                            </p>
                            <p style="margin: 0; color: #0d47a1;">
                                Vous n'avez pas encore créé de colis avec votre compte agent. 
                                Les colis créés avant l'activation de votre compte ou par d'autres utilisateurs ne sont pas comptabilisés.
                            </p>
                            <a href="<?php echo admin_url('admin.php?page=colis224-parcels&action=add'); ?>" class="button button-primary" style="margin-top: 10px;">
                                <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                                Créer votre premier colis
                            </a>
                        </div>
                    <?php elseif ($parcels_by_this_agent == 0 && $parcels_with_created_by == 0): ?>
                        <div style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
                            <p style="margin: 0 0 10px 0; color: #0d47a1; font-weight: bold;">
                                ℹ️ Information
                            </p>
                            <p style="margin: 0; color: #0d47a1;">
                                Aucun colis dans la base de données n'a de <code>created_by</code> défini. 
                                Cela signifie que tous les colis ont été créés avant l'ajout de cette fonctionnalité.
                            </p>
                            <p style="margin: 10px 0 0 0; color: #0d47a1;">
                                <strong>Solution :</strong> Créez un nouveau colis avec votre compte agent pour voir vos statistiques.
                            </p>
                            <a href="<?php echo admin_url('admin.php?page=colis224-parcels&action=add'); ?>" class="button button-primary" style="margin-top: 10px;">
                                <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                                Créer un colis
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($is_driver): ?>
                <!-- Dashboard Livreur -->
                <div class="colis224-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; margin-bottom: 20px; padding: 20px;">
                    <h2 style="color: white; margin: 0 0 10px 0;">
                        <span class="dashicons dashicons-car" style="vertical-align: middle;"></span>
                        Tableau de Bord Livreur
                    </h2>
                    <p style="margin: 0; opacity: 0.9;">Bienvenue, <?php echo esc_html(wp_get_current_user()->display_name); ?> !</p>
                </div>
            <?php elseif ($is_accountant): ?>
                <!-- Dashboard Comptable -->
                <div class="colis224-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; margin-bottom: 20px; padding: 20px;">
                    <h2 style="color: white; margin: 0 0 10px 0;">
                        <span class="dashicons dashicons-calculator" style="vertical-align: middle;"></span>
                        Tableau de Bord Comptable
                    </h2>
                    <p style="margin: 0; opacity: 0.9;">Bienvenue, <?php echo esc_html(wp_get_current_user()->display_name); ?> !</p>
                </div>
            <?php endif; ?>

            <!-- Hook pour filtres avancés (v2.18.22) -->
            <?php do_action('colis224_dashboard_before_stats'); ?>

            <!-- Cartes de statistiques principales -->
            <div class="colis224-dashboard-grid">
                <?php
                // Vérifier si l'utilisateur est agent, éditeur ou auteur (v2.18.3)
                $current_user = wp_get_current_user();
                $is_non_financial_role = $is_agent || $is_driver ||
                                        in_array('editor', $current_user->roles) ||
                                        in_array('author', $current_user->roles);

                if (!$is_non_financial_role): // Masquer CA pour agents, éditeurs, auteurs et livreurs
                ?>
                <!-- Chiffre d'affaires -->
                <div class="colis224-card colis224-card-blue">
                    <div class="colis224-card-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="colis224-card-content">
                        <h3>Chiffre d'Affaires</h3>
                        <p class="colis224-stat-value"><?php echo number_format($stats['revenue_total'], 0, ',', ' '); ?> GNF</p>
                        <p class="colis224-stat-label">
                            Aujourd'hui: <?php echo number_format($stats['revenue_today'], 0, ',', ' '); ?> GNF
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Nombre de colis -->
                <div class="colis224-card colis224-card-green">
                    <div class="colis224-card-icon">
                        <span class="dashicons dashicons-archive"></span>
                    </div>
                    <div class="colis224-card-content">
                        <h3><?php 
                            if ($is_agent) echo 'Mes Colis';
                            elseif ($is_driver) echo 'Mes Livraisons';
                            else echo 'Colis';
                        ?></h3>
                        <p class="colis224-stat-value"><?php echo $stats['parcels_total']; ?></p>
                        <p class="colis224-stat-label">
                            <?php 
                            if ($is_driver) {
                                echo 'En cours: ' . $stats['parcels_transit'];
                            } else {
                                echo 'En transit: ' . $stats['parcels_transit'] . ' | Livrés: ' . $stats['parcels_delivered'];
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <?php if (!$is_non_financial_role): // Seulement pour admin/comptable - masqué pour agents, éditeurs, auteurs, livreurs ?>
                <!-- Dépenses -->
                <div class="colis224-card colis224-card-orange">
                    <div class="colis224-card-icon">
                        <span class="dashicons dashicons-calculator"></span>
                    </div>
                    <div class="colis224-card-content">
                        <h3>Dépenses</h3>
                        <p class="colis224-stat-value"><?php echo number_format($stats['expenses_total'], 0, ',', ' '); ?> GNF</p>
                        <p class="colis224-stat-label">
                            Ce mois: <?php echo number_format($stats['expenses_month'], 0, ',', ' '); ?> GNF
                        </p>
                    </div>
                </div>

                <!-- Bénéfice net -->
                <div class="colis224-card colis224-card-purple">
                    <div class="colis224-card-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="colis224-card-content">
                        <h3>Bénéfice Net</h3>
                        <p class="colis224-stat-value"><?php echo number_format($stats['profit'], 0, ',', ' '); ?> GNF</p>
                        <p class="colis224-stat-label">
                            Revenus - Dépenses
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($is_agent): ?>
                <!-- Colis en attente de validation (pour agents) -->
                <div class="colis224-card colis224-card-orange">
                    <div class="colis224-card-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="colis224-card-content">
                        <h3>En Attente</h3>
                        <p class="colis224-stat-value"><?php echo $stats['parcels_pending_validation']; ?></p>
                        <p class="colis224-stat-label">
                            Colis en attente de validation
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Alerte : Colis en attente de validation (v2.11.0) - Seulement pour admin/manager -->
            <?php if (!$is_agent && !$is_driver && $stats['parcels_pending_validation'] > 0): ?>
            <div class="colis224-card" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-left: 5px solid #f0a000; margin-top: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1;">
                        <h2 style="margin: 0 0 10px 0; color: #856404; display: flex; align-items: center;">
                            <span class="dashicons dashicons-warning" style="font-size: 32px; margin-right: 10px;"></span>
                            <strong><?php echo $stats['parcels_pending_validation']; ?> Colis en Attente de Validation</strong>
                        </h2>
                        <p style="margin: 0; color: #856404; font-size: 15px;">
                            Des colis créés par des agents nécessitent votre approbation avant d'être activés dans le système.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo admin_url('admin.php?page=colis224-validation'); ?>" class="button button-primary button-large" style="background: #f0a000; border-color: #f0a000; box-shadow: 0 2px 8px rgba(240,160,0,0.3);">
                            <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span>
                            Voir les Colis à Valider
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hook pour statistiques filtrées (v2.18.22) -->
            <?php do_action('colis224_dashboard_after_main_stats'); ?>

            <!-- Deuxième ligne de statistiques -->
            <div class="colis224-dashboard-grid colis224-grid-4">
                <?php if (!$is_driver): ?>
                <!-- Clients -->
                <div class="colis224-card colis224-card-light">
                    <h4><span class="dashicons dashicons-groups"></span> <?php echo $is_agent ? 'Mes Clients' : 'Clients'; ?></h4>
                    <p class="colis224-big-number"><?php echo $stats['clients_total']; ?></p>
                </div>
                <?php endif; ?>

                <?php if ($is_admin): ?>
                <!-- Partenaires -->
                <div class="colis224-card colis224-card-light">
                    <h4><span class="dashicons dashicons-businessman"></span> Partenaires</h4>
                    <p class="colis224-big-number"><?php echo $stats['partners_total']; ?></p>
                </div>

                <!-- Livreurs -->
                <div class="colis224-card colis224-card-light">
                    <h4><span class="dashicons dashicons-car"></span> Livreurs</h4>
                    <p class="colis224-big-number"><?php echo $stats['drivers_total']; ?></p>
                </div>
                <?php endif; ?>

                <?php if (!$is_driver && !$is_agent): ?>
                <!-- Impayés -->
                <div class="colis224-card colis224-card-light">
                    <h4><span class="dashicons dashicons-warning"></span> Impayés</h4>
                    <p class="colis224-big-number"><?php echo number_format($stats['unpaid_total'], 0, ',', ' '); ?> GNF</p>
                </div>
                <?php endif; ?>

                <?php if ($is_agent): ?>
                <!-- Colis validés (pour agents) -->
                <div class="colis224-card colis224-card-light">
                    <h4><span class="dashicons dashicons-yes-alt"></span> Colis Validés</h4>
                    <p class="colis224-big-number"><?php echo $stats['parcels_approved']; ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Graphiques et détails -->
            <?php if (!$is_driver): ?>
            <div class="colis224-dashboard-row">
                <!-- Colis par statut -->
                <div class="colis224-card colis224-half-width">
                    <h3><span class="dashicons dashicons-chart-bar"></span> <?php echo $is_agent ? 'Mes Colis par Statut' : 'Colis par Statut'; ?></h3>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>Statut</th>
                                <th>Nombre</th>
                                <th>Pourcentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats['parcels_by_status'])): ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Aucun colis</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($stats['parcels_by_status'] as $status => $count): ?>
                                <tr>
                                    <td><?php echo esc_html($status); ?></td>
                                    <td><?php echo $count; ?></td>
                                    <td>
                                        <?php
                                        $percentage = $stats['parcels_total'] > 0
                                            ? round(($count / $stats['parcels_total']) * 100, 1)
                                            : 0;
                                        echo $percentage . '%';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Colis par pays -->
                <?php if (!$is_agent): ?>
                <div class="colis224-card colis224-half-width">
                    <h3><span class="dashicons dashicons-admin-site"></span> Colis par Pays d'Origine</h3>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>Pays</th>
                                <th>Nombre</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats['parcels_by_country'])): ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">Aucune donnée</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($stats['parcels_by_country'] as $country): ?>
                                <tr>
                                    <td><?php echo esc_html($country['country'] ?: 'Non spécifié'); ?></td>
                                    <td><?php echo $country['count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <!-- Pour les agents : Colis par statut de validation -->
                <div class="colis224-card colis224-half-width">
                    <h3><span class="dashicons dashicons-yes-alt"></span> Mes Colis par Statut de Validation</h3>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>Statut</th>
                                <th>Nombre</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>✅ Validés</td>
                                <td><strong><?php echo $stats['parcels_approved']; ?></strong></td>
                            </tr>
                            <tr>
                                <td>⏳ En attente</td>
                                <td><strong><?php echo $stats['parcels_pending_validation']; ?></strong></td>
                            </tr>
                            <tr>
                                <td>❌ Rejetés</td>
                                <td><strong><?php echo $stats['parcels_rejected']; ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Statistiques par équipe et partenaires - Seulement pour admin -->
            <?php if ($is_admin): ?>
            <div class="colis224-dashboard-row">
                <!-- Colis par livreur -->
                <div class="colis224-card colis224-third-width">
                    <h3><span class="dashicons dashicons-car"></span> Colis par Livreur</h3>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>Livreur</th>
                                <th>Colis en cours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats['parcels_by_driver'])): ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">Aucun colis assigné</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($stats['parcels_by_driver'] as $driver): ?>
                                <tr>
                                    <td><?php echo esc_html($driver['driver_name']); ?></td>
                                    <td><strong><?php echo $driver['count']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Colis par agent -->
                <div class="colis224-card colis224-third-width">
                    <h3><span class="dashicons dashicons-businessman"></span> Colis par Agent</h3>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Total enregistré</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats['parcels_by_agent'])): ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">Aucune donnée disponible</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($stats['parcels_by_agent'] as $agent): ?>
                                <tr>
                                    <td><?php echo esc_html($agent['agent_name']); ?></td>
                                    <td><strong><?php echo $agent['count']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Colis par partenaire -->
                <div class="colis224-card colis224-third-width">
                    <h3><span class="dashicons dashicons-admin-multisite"></span> Transactions Partenaires</h3>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>Partenaire</th>
                                <th>Colis confiés</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stats['parcels_by_partner'])): ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">Aucune transaction</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($stats['parcels_by_partner'] as $partner): ?>
                                <tr>
                                    <td><?php echo esc_html($partner['partner_name']); ?></td>
                                    <td><strong><?php echo $partner['count']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Programme de Fidélité - Seulement pour admin -->
            <?php if ($is_admin): ?>
            <div class="colis224-dashboard-row">
                <!-- Top 10 Clients Fidèles -->
                <div class="colis224-card colis224-half-width">
                    <h3><span class="dashicons dashicons-awards"></span> Top 10 Clients Fidèles</h3>
                    <?php self::display_top_loyalty_clients(); ?>
                </div>

                <!-- Statistiques du Programme -->
                <div class="colis224-card colis224-half-width">
                    <h3><span class="dashicons dashicons-chart-pie"></span> Programme de Fidélité</h3>
                    <?php self::display_loyalty_stats(); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Derniers colis -->
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-list-view"></span> <?php 
                    if ($is_agent) echo 'Mes Derniers Colis Enregistrés';
                    elseif ($is_driver) echo 'Mes Dernières Livraisons';
                    else echo 'Derniers Colis Enregistrés';
                ?></h3>
                <?php self::display_recent_parcels($user_role, $current_user_id); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Récupérer les statistiques (adaptées selon le rôle)
     */
    private static function get_statistics($user_role = null, $user_id = null) {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_revenues = $wpdb->prefix . 'colis224_revenues';
        $table_expenses = $wpdb->prefix . 'colis224_expenses';
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_partners = $wpdb->prefix . 'colis224_partners';
        $table_drivers = $wpdb->prefix . 'colis224_drivers';
        $table_countries = $wpdb->prefix . 'colis224_countries';

        $is_admin = current_user_can('manage_options');
        $is_agent = ($user_role === 'colis224_agent');
        $is_driver = ($user_role === 'colis224_driver');
        $is_accountant = ($user_role === 'colis224_accountant');

        // Construire les conditions WHERE selon le rôle
        $where_parcels = "1=1";
        $where_revenues = "1=1";
        $where_clients = "1=1";

        if ($is_agent && $user_id) {
            // Agent : seulement ses colis créés
            // Vérifier d'abord si la colonne created_by existe
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'created_by'");
            if (!empty($columns)) {
                $where_parcels = $wpdb->prepare("created_by = %d", $user_id);
                $where_clients = $wpdb->prepare("id IN (SELECT DISTINCT client_id FROM $table_parcels WHERE created_by = %d AND client_id IS NOT NULL)", $user_id);
            } else {
                // Si la colonne n'existe pas, l'agent ne voit rien (doit exécuter la migration)
                $where_parcels = "1=0";
                $where_clients = "1=0";
            }
        } elseif ($is_driver && $user_id) {
            // Livreur : seulement les colis qui lui sont assignés
            // Trouver l'ID du livreur en comparant le nom ou l'email avec l'utilisateur WordPress
            $current_user = wp_get_current_user();
            $driver = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}colis224_drivers 
                WHERE (name = %s OR email = %s) AND is_active = 1 
                LIMIT 1",
                $current_user->display_name,
                $current_user->user_email
            ));
            if ($driver) {
                $where_parcels = $wpdb->prepare("driver_id = %d", $driver->id);
            } else {
                // Si aucun driver trouvé, essayer de trouver par email ou nom partiel
                $driver = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}colis224_drivers 
                    WHERE (email LIKE %s OR name LIKE %s) AND is_active = 1 
                    LIMIT 1",
                    '%' . $wpdb->esc_like($current_user->user_email) . '%',
                    '%' . $wpdb->esc_like($current_user->display_name) . '%'
                ));
                if ($driver) {
                    $where_parcels = $wpdb->prepare("driver_id = %d", $driver->id);
                } else {
                    $where_parcels = "1=0"; // Aucun colis si pas de driver associé
                }
            }
        }

        // Revenus (seulement pour admin/comptable)
        $revenue_total = 0;
        $revenue_today = 0;
        if (!$is_agent && !$is_driver) {
            $revenue_total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table_revenues WHERE $where_revenues");
            $revenue_today = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM $table_revenues WHERE revenue_date = %s",
                current_time('Y-m-d')
            ));
        } elseif ($is_agent && $user_id) {
            // Pour l'agent : CA de ses colis
            // Vérifier si la colonne created_by existe
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'created_by'");
            if (!empty($columns)) {
                $revenue_total = $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(total_amount), 0) FROM $table_parcels WHERE created_by = %d",
                    $user_id
                ));
            } else {
                $revenue_total = 0;
            }
        }

        // Dépenses (seulement pour admin/comptable)
        $expenses_total = 0;
        $expenses_month = 0;
        if (!$is_agent && !$is_driver) {
            $expenses_total = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table_expenses");
            $expenses_month = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM $table_expenses
                WHERE MONTH(expense_date) = %d AND YEAR(expense_date) = %d",
                date('n'),
                date('Y')
            ));
        }

        // Colis
        $parcels_total = $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE $where_parcels");
        $parcels_transit = $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE $where_parcels AND status = 'En transit'");
        $parcels_delivered = $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE $where_parcels AND status = 'Livré'");

        // Colis en attente de validation (v2.11.0)
        if ($is_agent && $user_id) {
            // Vérifier si les colonnes existent
            $columns_created = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'created_by'");
            $columns_validation = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'validation_status'");
            
            if (!empty($columns_created) && !empty($columns_validation)) {
                $parcels_pending_validation = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_parcels WHERE created_by = %d AND validation_status = 'pending'",
                    $user_id
                ));
                $parcels_approved = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_parcels WHERE created_by = %d AND validation_status = 'approved'",
                    $user_id
                ));
                $parcels_rejected = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_parcels WHERE created_by = %d AND validation_status = 'rejected'",
                    $user_id
                ));
            } else {
                $parcels_pending_validation = 0;
                $parcels_approved = 0;
                $parcels_rejected = 0;
            }
        } else {
            $parcels_pending_validation = $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE validation_status = 'pending'");
            $parcels_approved = 0;
            $parcels_rejected = 0;
        }

        // Colis par statut
        $parcels_by_status_query = "SELECT status, COUNT(*) as count FROM $table_parcels WHERE $where_parcels GROUP BY status";
        $parcels_by_status = $wpdb->get_results($parcels_by_status_query, OBJECT_K);
        $status_array = array();
        foreach ($parcels_by_status as $status => $data) {
            $status_array[$status] = $data->count;
        }

        // Colis par pays (seulement pour admin)
        $parcels_by_country = array();
        if ($is_admin) {
            $parcels_by_country = $wpdb->get_results("
                SELECT c.name as country, COUNT(p.id) as count
                FROM $table_parcels p
                LEFT JOIN $table_countries c ON p.origin_country_id = c.id
                WHERE $where_parcels
                GROUP BY c.name
                ORDER BY count DESC
                LIMIT 10
            ", ARRAY_A);
        }

        // Autres compteurs
        $clients_total = $wpdb->get_var("SELECT COUNT(*) FROM $table_clients WHERE $where_clients");
        $partners_total = 0;
        $drivers_total = 0;
        if ($is_admin) {
            $partners_total = $wpdb->get_var("SELECT COUNT(*) FROM $table_partners");
            $drivers_total = $wpdb->get_var("SELECT COUNT(*) FROM $table_drivers WHERE is_active = 1");
        }

        // Impayés (seulement pour admin/comptable)
        $unpaid_total = 0;
        if (!$is_agent && !$is_driver) {
            $unpaid_total = $wpdb->get_var(
                "SELECT COALESCE(SUM(remaining_amount), 0) FROM $table_parcels
                WHERE $where_parcels AND payment_status IN ('Non payé', 'Partiel')"
            );
        }

        // Colis par livreur (en cours uniquement: non livrés)
        $parcels_by_driver = $wpdb->get_results("
            SELECT d.name as driver_name, COUNT(p.id) as count
            FROM {$wpdb->prefix}colis224_drivers d
            LEFT JOIN $table_parcels p ON d.id = p.driver_id AND p.status != 'Livré'
            WHERE d.is_active = 1
            GROUP BY d.id, d.name
            HAVING count > 0
            ORDER BY count DESC
            LIMIT 10
        ", ARRAY_A);

        // Colis par agent (utilise maintenant created_by)
        $parcels_by_agent = array();
        if ($is_admin) {
            // Vérifier si la colonne created_by existe
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'created_by'");
            if (!empty($columns)) {
                $parcels_by_agent = $wpdb->get_results("
                    SELECT u.display_name as agent_name, COUNT(p.id) as count
                    FROM $table_parcels p
                    INNER JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
                    WHERE p.created_by IS NOT NULL
                    GROUP BY p.created_by, u.display_name
                    ORDER BY count DESC
                    LIMIT 10
                ", ARRAY_A);
            }
        }

        // Colis par partenaire (transactions de type 'confié')
        $table_partner_trans = $wpdb->prefix . 'colis224_partner_transactions';
        $parcels_by_partner = $wpdb->get_results("
            SELECT p.name as partner_name, COUNT(pt.id) as count
            FROM $table_partners p
            LEFT JOIN $table_partner_trans pt ON p.id = pt.partner_id
                AND pt.transaction_type = 'confié'
                AND pt.parcel_status NOT IN ('Livré')
            GROUP BY p.id, p.name
            HAVING count > 0
            ORDER BY count DESC
            LIMIT 10
        ", ARRAY_A);

        return array(
            'revenue_total' => floatval($revenue_total),
            'revenue_today' => floatval($revenue_today),
            'expenses_total' => floatval($expenses_total),
            'expenses_month' => floatval($expenses_month),
            'profit' => floatval($revenue_total) - floatval($expenses_total),
            'parcels_total' => intval($parcels_total),
            'parcels_transit' => intval($parcels_transit),
            'parcels_delivered' => intval($parcels_delivered),
            'parcels_pending_validation' => intval($parcels_pending_validation),
            'parcels_approved' => isset($parcels_approved) ? intval($parcels_approved) : 0,
            'parcels_rejected' => isset($parcels_rejected) ? intval($parcels_rejected) : 0,
            'parcels_by_status' => $status_array,
            'parcels_by_country' => $parcels_by_country,
            'clients_total' => intval($clients_total),
            'partners_total' => intval($partners_total),
            'drivers_total' => intval($drivers_total),
            'unpaid_total' => floatval($unpaid_total),
            'parcels_by_driver' => $parcels_by_driver,
            'parcels_by_agent' => $parcels_by_agent,
            'parcels_by_partner' => $parcels_by_partner
        );
    }

    /**
     * Afficher les derniers colis (adapté selon le rôle)
     */
    private static function display_recent_parcels($user_role = null, $user_id = null) {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $is_admin = current_user_can('manage_options');
        $is_agent = ($user_role === 'colis224_agent');
        $is_driver = ($user_role === 'colis224_driver');

        $where = "1=1";
        
        if ($is_agent && $user_id) {
            // Vérifier si la colonne created_by existe
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'created_by'");
            if (!empty($columns)) {
                $where = $wpdb->prepare("p.created_by = %d", $user_id);
            } else {
                $where = "1=0"; // Aucun colis si la colonne n'existe pas
            }
        } elseif ($is_driver && $user_id) {
            // Trouver l'ID du livreur en comparant le nom ou l'email
            $current_user = wp_get_current_user();
            $driver = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}colis224_drivers 
                WHERE (name = %s OR email = %s) AND is_active = 1 
                LIMIT 1",
                $current_user->display_name,
                $current_user->user_email
            ));
            if (!$driver) {
                // Essayer de trouver par email ou nom partiel
                $driver = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}colis224_drivers 
                    WHERE (email LIKE %s OR name LIKE %s) AND is_active = 1 
                    LIMIT 1",
                    '%' . $wpdb->esc_like($current_user->user_email) . '%',
                    '%' . $wpdb->esc_like($current_user->display_name) . '%'
                ));
            }
            if ($driver) {
                $where = $wpdb->prepare("p.driver_id = %d", $driver->id);
            } else {
                $where = "1=0";
            }
        }

        $recent_parcels = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.name as client_name
            FROM $table_parcels p
            LEFT JOIN $table_clients c ON p.client_id = c.id
            WHERE $where
            ORDER BY p.created_at DESC
            LIMIT %d
        ", 10));

        if (empty($recent_parcels)) {
            echo '<p>Aucun colis enregistré pour le moment.</p>';
            return;
        }

        // Vérifier si l'utilisateur a un rôle non-financier (v2.18.3)
        $current_user = wp_get_current_user();
        $hide_financial = $is_agent || $is_driver ||
                         in_array('editor', $current_user->roles) ||
                         in_array('author', $current_user->roles);

        ?>
        <table class="colis224-table">
            <thead>
                <tr>
                    <th>N° Suivi</th>
                    <th>Client</th>
                    <th>Destinataire</th>
                    <th>Statut</th>
                    <?php if (!$hide_financial): ?><th>Montant</th><?php endif; ?>
                    <?php if (!$hide_financial): ?><th>Paiement</th><?php endif; ?>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_parcels as $parcel): ?>
                <tr>
                    <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                    <td><?php echo esc_html($parcel->client_name ?: '-'); ?></td>
                    <td><?php echo esc_html($parcel->recipient_name); ?></td>
                    <td><span class="colis224-badge colis224-badge-<?php echo sanitize_title($parcel->status); ?>">
                        <?php echo esc_html($parcel->status); ?>
                    </span></td>
                    <?php if (!$hide_financial): ?>
                    <td><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                    <td><span class="colis224-badge colis224-badge-payment-<?php echo sanitize_title($parcel->payment_status); ?>">
                        <?php echo esc_html($parcel->payment_status); ?>
                    </span></td>
                    <?php endif; ?>
                    <td><?php echo date('d/m/Y', strtotime($parcel->created_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Afficher le Top 10 des clients fidèles
     */
    private static function display_top_loyalty_clients() {
        global $wpdb;

        $table_members = $wpdb->prefix . 'colis224_loyalty_members';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $top_clients = $wpdb->get_results("
            SELECT c.name, c.phone, m.total_points, m.available_points, m.tier
            FROM $table_members m
            INNER JOIN $table_clients c ON m.client_id = c.id
            ORDER BY m.total_points DESC
            LIMIT 10
        ");

        if (empty($top_clients)) {
            echo '<p style="text-align: center; padding: 20px; color: #666;">Aucun client inscrit au programme de fidélité.</p>';
            return;
        }

        // Couleurs des tiers
        $tier_colors = array(
            'Platinum' => '#E5E4E2',
            'Gold' => '#FFD700',
            'Silver' => '#C0C0C0',
            'Bronze' => '#CD7F32'
        );

        $tier_icons = array(
            'Platinum' => '💎',
            'Gold' => '🥇',
            'Silver' => '🥈',
            'Bronze' => '🥉'
        );

        ?>
        <table class="colis224-table">
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Client</th>
                    <th>Tier</th>
                    <th>Points Totaux</th>
                    <th>Points Disponibles</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; ?>
                <?php foreach ($top_clients as $client): ?>
                <tr>
                    <td><strong><?php echo $rank++; ?></strong></td>
                    <td>
                        <?php echo esc_html($client->name); ?><br>
                        <small><?php echo esc_html($client->phone); ?></small>
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: <?php echo $tier_colors[$client->tier]; ?>; color: #333;">
                            <?php echo $tier_icons[$client->tier]; ?> <?php echo esc_html($client->tier); ?>
                        </span>
                    </td>
                    <td><strong><?php echo number_format($client->total_points, 0, ',', ' '); ?></strong> pts</td>
                    <td><?php echo number_format($client->available_points, 0, ',', ' '); ?> pts</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Afficher les statistiques du programme de fidélité
     */
    private static function display_loyalty_stats() {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-loyalty.php';
        $stats = Colis224_Loyalty::get_loyalty_stats();

        ?>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                <div style="font-size: 36px; font-weight: bold;"><?php echo number_format($stats['total_members'], 0, ',', ' '); ?></div>
                <div style="font-size: 14px; margin-top: 5px;">Membres Inscrits</div>
            </div>
            <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                <div style="font-size: 36px; font-weight: bold;"><?php echo number_format($stats['total_points_awarded'], 0, ',', ' '); ?></div>
                <div style="font-size: 14px; margin-top: 5px;">Points Attribués</div>
            </div>
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                <div style="font-size: 36px; font-weight: bold;"><?php echo number_format($stats['total_points_redeemed'], 0, ',', ' '); ?></div>
                <div style="font-size: 14px; margin-top: 5px;">Points Échangés</div>
            </div>
            <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                <div style="font-size: 36px; font-weight: bold;"><?php echo number_format($stats['total_redemptions'], 0, ',', ' '); ?></div>
                <div style="font-size: 14px; margin-top: 5px;">Récompenses Échangées</div>
            </div>
        </div>

        <h4 style="margin: 20px 0 10px 0;"><span class="dashicons dashicons-groups"></span> Répartition par Tier</h4>
        <table class="colis224-table">
            <thead>
                <tr>
                    <th>Tier</th>
                    <th>Nombre de Membres</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>💎 Platinum</td>
                    <td><strong><?php echo number_format($stats['members_by_tier']['Platinum'], 0, ',', ' '); ?></strong></td>
                </tr>
                <tr>
                    <td>🥇 Gold</td>
                    <td><strong><?php echo number_format($stats['members_by_tier']['Gold'], 0, ',', ' '); ?></strong></td>
                </tr>
                <tr>
                    <td>🥈 Silver</td>
                    <td><strong><?php echo number_format($stats['members_by_tier']['Silver'], 0, ',', ' '); ?></strong></td>
                </tr>
                <tr>
                    <td>🥉 Bronze</td>
                    <td><strong><?php echo number_format($stats['members_by_tier']['Bronze'], 0, ',', ' '); ?></strong></td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}
