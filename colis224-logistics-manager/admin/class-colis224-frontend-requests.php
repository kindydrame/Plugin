<?php
/**
 * MODULE: Gestion des Devis Frontend
 *
 * Affiche tous les devis/demandes soumis via le calculateur frontend
 *
 * @package Colis224_Logistics_Manager
 * @since 2.20.6
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Frontend_Requests {

    public static function display_page() {
        global $wpdb;
        $table_requests = $wpdb->prefix . 'colis224_frontend_requests';

        // Filtres
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $service_filter = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
        $origin_filter = isset($_GET['origin']) ? sanitize_text_field($_GET['origin']) : '';

        // Construction de la requête WHERE
        $where = "1=1";
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (nom LIKE %s OR prenom LIKE %s OR telephone LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        if (!empty($service_filter)) {
            $where .= $wpdb->prepare(" AND type_service = %s", $service_filter);
        }

        if (!empty($origin_filter)) {
            $where .= $wpdb->prepare(" AND origine = %s", $origin_filter);
        }

        // Récupérer les demandes
        $requests = $wpdb->get_results("SELECT * FROM $table_requests WHERE $where ORDER BY created_at DESC");

        ?>
        <div class="wrap colis224-wrap">
            <div class="colis224-header">
                <h1>
                    <span class="colis224-icon">📋</span>
                    Devis & Demandes Frontend
                </h1>
                <p class="colis224-subtitle">
                    Toutes les demandes soumises via le calculateur client
                </p>
            </div>

            <!-- Filtres -->
            <div class="colis224-filters" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <form method="get" action="">
                    <input type="hidden" name="page" value="colis224-frontend-requests">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <!-- Recherche -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">🔍 Recherche</label>
                            <input type="text" name="s" value="<?php echo esc_attr($search); ?>"
                                   placeholder="Nom, prénom, téléphone..."
                                   style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <!-- Type de service -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">📦 Service</label>
                            <select name="service" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">Tous les services</option>
                                <option value="cas1" <?php selected($service_filter, 'cas1'); ?>>CAS 1 - Je veux acheter</option>
                                <option value="cas2" <?php selected($service_filter, 'cas2'); ?>>CAS 2 - J'ai déjà acheté</option>
                                <option value="cas3" <?php selected($service_filter, 'cas3'); ?>>CAS 3 - Valider ma commande</option>
                                <option value="cas4" <?php selected($service_filter, 'cas4'); ?>>CAS 4 - Dépôt en agence</option>
                            </select>
                        </div>

                        <!-- Pays d'origine -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">🌍 Origine</label>
                            <select name="origin" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">Tous les pays</option>
                                <option value="chine" <?php selected($origin_filter, 'chine'); ?>>🇨🇳 Chine</option>
                                <option value="france" <?php selected($origin_filter, 'france'); ?>>🇫🇷 France</option>
                                <option value="guinee" <?php selected($origin_filter, 'guinee'); ?>>🇬🇳 Guinée</option>
                                <option value="senegal" <?php selected($origin_filter, 'senegal'); ?>>🇸🇳 Sénégal</option>
                                <option value="maroc" <?php selected($origin_filter, 'maroc'); ?>>🇲🇦 Maroc</option>
                                <option value="cote_ivoire" <?php selected($origin_filter, 'cote_ivoire'); ?>>🇨🇮 Côte d'Ivoire</option>
                            </select>
                        </div>

                        <!-- Boutons -->
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="button button-primary" style="padding: 8px 20px;">
                                Filtrer
                            </button>
                            <a href="?page=colis224-frontend-requests" class="button" style="padding: 8px 20px; text-decoration: none;">
                                Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Statistiques rapides -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <?php
                $total_requests = count($requests);
                $cas1_count = count(array_filter($requests, function($r) { return $r->type_service === 'cas1'; }));
                $cas2_count = count(array_filter($requests, function($r) { return $r->type_service === 'cas2'; }));
                $cas3_count = count(array_filter($requests, function($r) { return $r->type_service === 'cas3'; }));
                ?>
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 700;"><?php echo $total_requests; ?></div>
                    <div style="font-size: 14px; opacity: 0.9;">Total demandes</div>
                </div>
                <div style="background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 700;"><?php echo $cas1_count; ?></div>
                    <div style="font-size: 14px; opacity: 0.9;">CAS 1 - Achats</div>
                </div>
                <div style="background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 700;"><?php echo $cas2_count; ?></div>
                    <div style="font-size: 14px; opacity: 0.9;">CAS 2 - Déjà acheté</div>
                </div>
                <div style="background: linear-gradient(135deg, #fb923c 0%, #f97316 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 700;"><?php echo $cas3_count; ?></div>
                    <div style="font-size: 14px; opacity: 0.9;">CAS 3 - Validation</div>
                </div>
            </div>

            <!-- Tableau des demandes -->
            <div class="colis224-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Client</th>
                            <th>Téléphone</th>
                            <th style="width: 100px;">Service</th>
                            <th style="width: 100px;">Origine</th>
                            <th style="width: 100px;">Mode</th>
                            <th>Détails</th>
                            <th style="width: 150px;">Date</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                                    📭 Aucune demande trouvée
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                // Déterminer l'icône et la couleur du service
                                $service_badge = '';
                                switch ($request->type_service) {
                                    case 'cas1':
                                        $service_badge = '<span style="background: #4ade80; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">🛒 CAS 1</span>';
                                        break;
                                    case 'cas2':
                                        $service_badge = '<span style="background: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">📦 CAS 2</span>';
                                        break;
                                    case 'cas3':
                                        $service_badge = '<span style="background: #f97316; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">✅ CAS 3</span>';
                                        break;
                                    case 'cas4':
                                        $service_badge = '<span style="background: #8b5cf6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">🏢 CAS 4</span>';
                                        break;
                                }

                                // Formater le téléphone pour WhatsApp
                                $phone_clean = preg_replace('/[^0-9]/', '', $request->telephone);
                                $whatsapp_link = 'https://wa.me/' . $phone_clean;
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $request->id; ?></strong></td>
                                    <td>
                                        <strong><?php echo esc_html($request->prenom . ' ' . $request->nom); ?></strong>
                                        <?php if ($request->pays_client): ?>
                                            <br><small style="color: #666;">🌍 <?php echo esc_html(strtoupper($request->pays_client)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($whatsapp_link); ?>"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           style="color: #25d366; text-decoration: none; font-weight: 600;"
                                           title="Contacter via WhatsApp">
                                            💬 <?php echo esc_html($request->telephone); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $service_badge; ?></td>
                                    <td>
                                        <?php if ($request->origine === 'chine'): ?>
                                            🇨🇳 Chine
                                        <?php elseif ($request->origine === 'france'): ?>
                                            🇫🇷 France
                                        <?php elseif ($request->origine === 'guinee'): ?>
                                            🇬🇳 Guinée
                                        <?php elseif ($request->origine): ?>
                                            <?php echo esc_html(ucfirst($request->origine)); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($request->mode_livraison === 'avion'): ?>
                                            ✈️ Avion
                                        <?php elseif ($request->mode_livraison === 'bateau'): ?>
                                            🚢 Bateau
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($request->type_service === 'cas1'): ?>
                                            <?php if ($request->quantites): ?>
                                                <small><strong>Qtés:</strong> <?php echo esc_html(substr($request->quantites, 0, 50)); ?><?php echo strlen($request->quantites) > 50 ? '...' : ''; ?></small><br>
                                            <?php endif; ?>
                                            <?php if ($request->modeles): ?>
                                                <small><strong>Modèles:</strong> <?php echo esc_html(substr($request->modeles, 0, 50)); ?><?php echo strlen($request->modeles) > 50 ? '...' : ''; ?></small>
                                            <?php endif; ?>
                                        <?php elseif ($request->type_service === 'cas3'): ?>
                                            <?php if ($request->panier_details): ?>
                                                <small><?php echo esc_html(substr($request->panier_details, 0, 80)); ?><?php echo strlen($request->panier_details) > 80 ? '...' : ''; ?></small>
                                            <?php endif; ?>
                                        <?php elseif ($request->type_service === 'cas4'): ?>
                                            <?php if ($request->agency): ?>
                                                <small><strong>Agence:</strong> <?php echo esc_html(ucfirst($request->agency)); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($request->destination): ?>
                                                <small><strong>Destination:</strong> <?php echo esc_html(ucfirst($request->destination)); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small style="color: #666;">
                                            <?php echo date('d/m/Y', strtotime($request->created_at)); ?><br>
                                            <?php echo date('H:i', strtotime($request->created_at)); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($whatsapp_link); ?>"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="button button-small button-primary"
                                           style="background: #25d366; border-color: #25d366;">
                                            💬 WhatsApp
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <style>
                .colis224-wrap {
                    margin: 20px 20px 20px 0;
                }
                .colis224-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    border-radius: 12px;
                    margin-bottom: 30px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .colis224-header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 700;
                    color: white;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .colis224-icon {
                    font-size: 32px;
                }
                .colis224-subtitle {
                    margin: 8px 0 0;
                    opacity: 0.9;
                    font-size: 14px;
                }
                .colis224-table-container {
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .colis224-table-container table {
                    margin: 0;
                }
                .colis224-table-container th {
                    background: #f8f9fa;
                    font-weight: 600;
                    color: #1e293b;
                }
            </style>
        </div>
        <?php
    }
}
