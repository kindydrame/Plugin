<?php
/**
 * Interface d'import CSV
 *
 * @package Colis224_Logistics
 * @subpackage Admin
 * @version 2.15.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_CSV_Import_Admin {

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

        $step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'upload';

        if ($step === 'mapping' && isset($_SESSION['colis224_csv_file'])) {
            self::display_mapping_step();
        } elseif ($step === 'preview' && isset($_SESSION['colis224_csv_mapping'])) {
            self::display_preview_step();
        } elseif ($step === 'import' && isset($_SESSION['colis224_csv_mapping'])) {
            self::display_import_step();
        } else {
            self::display_upload_step();
        }
    }

    /**
     * Étape 1 : Upload du fichier
     */
    private static function display_upload_step() {
        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-upload"></span>
                Import CSV - Paiements
            </h1>

            <!-- Guide -->
            <div class="colis224-card" style="background: #e7f5fe; border-left: 4px solid #2271b1;">
                <h3 style="margin: 0 0 10px 0; color: #2271b1;">
                    <span class="dashicons dashicons-info"></span>
                    Comment importer vos paiements ?
                </h3>
                <ol style="margin: 0; padding-left: 20px;">
                    <li>Préparez votre fichier CSV avec les colonnes nécessaires</li>
                    <li>Uploadez le fichier ci-dessous</li>
                    <li>Associez les colonnes aux champs système</li>
                    <li>Prévisualisez l'import</li>
                    <li>Confirmez l'importation</li>
                </ol>
            </div>

            <!-- Template -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📄 Template CSV</h2>
                <p>Téléchargez un modèle de fichier CSV pour vous guider :</p>

                <form method="post" style="display: inline-block;">
                    <input type="hidden" name="action" value="download_template">
                    <?php wp_nonce_field('colis224_csv_template', 'template_nonce'); ?>
                    <button type="submit" class="button">
                        <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                        Télécharger le Template
                    </button>
                </form>

                <div style="margin-top: 15px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <strong>Colonnes suggérées :</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>N° Suivi</strong> : Numéro de suivi du colis</li>
                        <li><strong>Client</strong> : Nom du client (alternatif au n° suivi)</li>
                        <li><strong>Montant Payé</strong> : Montant du paiement</li>
                        <li><strong>Méthode Paiement</strong> : Espèces, Virement, Mobile Money, etc.</li>
                        <li><strong>Date Paiement</strong> : Date du paiement (optionnel)</li>
                        <li><strong>Notes</strong> : Remarques éventuelles (optionnel)</li>
                    </ul>
                </div>
            </div>

            <!-- Upload -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📤 Uploader votre fichier CSV</h2>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_csv">
                    <?php wp_nonce_field('colis224_csv_upload', 'upload_nonce'); ?>

                    <div class="colis224-form-group">
                        <label for="csv_file">Fichier CSV *</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required>
                        <small>Format accepté : .csv ou .txt (max 5 Mo)</small>
                    </div>

                    <div class="colis224-form-group">
                        <label for="delimiter">Délimiteur</label>
                        <select name="delimiter" id="delimiter">
                            <option value=",">Virgule (,)</option>
                            <option value=";">Point-virgule (;)</option>
                            <option value="\t">Tabulation</option>
                        </select>
                    </div>

                    <div class="colis224-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-upload"></span>
                            Uploader et Continuer
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Étape 2 : Mapping des colonnes
     */
    private static function display_mapping_step() {
        $csv_file = $_SESSION['colis224_csv_file'];
        $delimiter = $_SESSION['colis224_csv_delimiter'] ?? ',';

        $csv_data = Colis224_CSV_Importer::parse_csv($csv_file, $delimiter);
        $suggested_mapping = Colis224_CSV_Importer::detect_column_mapping($csv_data['headers']);

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-admin-settings"></span>
                Import CSV - Mapping des Colonnes
            </h1>

            <div class="colis224-card">
                <h2>🔗 Associez vos colonnes CSV aux champs système</h2>
                <p>
                    <strong><?php echo count($csv_data['headers']); ?> colonnes détectées</strong> dans votre fichier.
                    <?php echo $csv_data['total_rows']; ?> lignes de données.
                </p>

                <form method="post">
                    <input type="hidden" name="action" value="save_mapping">
                    <?php wp_nonce_field('colis224_csv_mapping', 'mapping_nonce'); ?>

                    <table class="widefat" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>Colonne CSV</th>
                                <th>Exemple de Donnée</th>
                                <th>→</th>
                                <th>Champ Système</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($csv_data['headers'] as $header):
                                $example = isset($csv_data['rows'][0][$header]) ? $csv_data['rows'][0][$header] : '-';
                                $suggested = $suggested_mapping[$header] ?? '';
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($header); ?></strong></td>
                                    <td><code><?php echo esc_html(substr($example, 0, 50)); ?></code></td>
                                    <td style="text-align: center;">→</td>
                                    <td>
                                        <select name="mapping[<?php echo esc_attr($header); ?>]" style="width: 100%;">
                                            <option value="">-- Ne pas importer --</option>
                                            <option value="tracking_number" <?php selected($suggested, 'tracking_number'); ?>>N° de Suivi</option>
                                            <option value="client_name" <?php selected($suggested, 'client_name'); ?>>Nom Client</option>
                                            <option value="phone" <?php selected($suggested, 'phone'); ?>>Téléphone</option>
                                            <option value="amount" <?php selected($suggested, 'amount'); ?>>Montant Total</option>
                                            <option value="paid_amount" <?php selected($suggested, 'paid_amount'); ?>>Montant Payé</option>
                                            <option value="payment_method" <?php selected($suggested, 'payment_method'); ?>>Méthode Paiement</option>
                                            <option value="payment_date" <?php selected($suggested, 'payment_date'); ?>>Date Paiement</option>
                                            <option value="notes" <?php selected($suggested, 'notes'); ?>>Notes</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="colis224-form-actions" style="margin-top: 20px;">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-visibility"></span>
                            Prévisualiser l'Import
                        </button>
                        <a href="?page=colis224-csv-import" class="button button-large">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Étape 3 : Prévisualisation
     */
    private static function display_preview_step() {
        $csv_file = $_SESSION['colis224_csv_file'];
        $delimiter = $_SESSION['colis224_csv_delimiter'] ?? ',';
        $mapping = $_SESSION['colis224_csv_mapping'];

        $csv_data = Colis224_CSV_Importer::parse_csv($csv_file, $delimiter);

        // Valider quelques lignes pour prévisualisation
        $preview_rows = array_slice($csv_data['rows'], 0, 10);
        $validations = array();

        foreach ($preview_rows as $row) {
            $validations[] = Colis224_CSV_Importer::validate_row($row, $mapping);
        }

        $valid_count = count(array_filter($validations, function($v) { return $v['valid']; }));
        $error_count = count($preview_rows) - $valid_count;

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-visibility"></span>
                Import CSV - Prévisualisation
            </h1>

            <!-- Stats -->
            <div class="colis224-dashboard-grid colis224-grid-4">
                <div class="colis224-card colis224-card-blue">
                    <h4><span class="dashicons dashicons-media-spreadsheet"></span> Total Lignes</h4>
                    <p class="colis224-big-number"><?php echo $csv_data['total_rows']; ?></p>
                </div>
                <div class="colis224-card colis224-card-green">
                    <h4><span class="dashicons dashicons-yes-alt"></span> Valides (échantillon)</h4>
                    <p class="colis224-big-number"><?php echo $valid_count; ?> / 10</p>
                </div>
                <div class="colis224-card colis224-card-orange">
                    <h4><span class="dashicons dashicons-warning"></span> Erreurs (échantillon)</h4>
                    <p class="colis224-big-number"><?php echo $error_count; ?> / 10</p>
                </div>
                <div class="colis224-card colis224-card-purple">
                    <h4><span class="dashicons dashicons-admin-settings"></span> Colonnes Mappées</h4>
                    <p class="colis224-big-number"><?php echo count(array_filter($mapping)); ?></p>
                </div>
            </div>

            <!-- Prévisualisation -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>👁️ Aperçu des 10 Premières Lignes</h2>

                <table class="widefat" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Statut</th>
                            <th>Données Extraites</th>
                            <th>Messages</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($validations as $index => $validation): ?>
                            <tr>
                                <td><?php echo $index + 2; ?></td>
                                <td>
                                    <?php if ($validation['valid']): ?>
                                        <span style="color: #46b450; font-weight: bold;">✓ Valide</span>
                                    <?php else: ?>
                                        <span style="color: #dc3232; font-weight: bold;">✗ Erreur</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php foreach ($validation['data'] as $field => $value): ?>
                                            <?php if (!empty($value)): ?>
                                                <strong><?php echo esc_html($field); ?>:</strong> <?php echo esc_html(substr($value, 0, 30)); ?><br>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($validation['errors'])): ?>
                                        <span style="color: #dc3232;">
                                            <?php echo implode('<br>', array_map('esc_html', $validation['errors'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($validation['warnings'])): ?>
                                        <span style="color: #f0a000;">
                                            <?php echo implode('<br>', array_map('esc_html', $validation['warnings'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Actions -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>🚀 Prêt à importer ?</h2>

                <?php if ($error_count > 0): ?>
                    <div style="background: #fff3cd; border: 1px solid #f0a000; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <p style="margin: 0; color: #856404;">
                            ⚠️ <strong>Attention :</strong> Des erreurs ont été détectées dans l'échantillon.
                            Les lignes en erreur seront ignorées lors de l'import.
                        </p>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="action" value="confirm_import">
                    <?php wp_nonce_field('colis224_csv_import', 'import_nonce'); ?>

                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-download"></span>
                        Lancer l'Importation
                    </button>
                    <a href="?page=colis224-csv-import&step=mapping" class="button button-large">Modifier le Mapping</a>
                    <a href="?page=colis224-csv-import" class="button button-large">Annuler</a>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Étape 4 : Import final
     */
    private static function display_import_step() {
        $csv_file = $_SESSION['colis224_csv_file'];
        $delimiter = $_SESSION['colis224_csv_delimiter'] ?? ',';
        $mapping = $_SESSION['colis224_csv_mapping'];

        // Effectuer l'import
        $result = Colis224_CSV_Importer::import_payments($csv_file, $mapping, array('delimiter' => $delimiter));

        // Nettoyer la session
        unset($_SESSION['colis224_csv_file']);
        unset($_SESSION['colis224_csv_delimiter']);
        unset($_SESSION['colis224_csv_mapping']);

        if (!$result['success']) {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            return;
        }

        $stats = $result['stats'];

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-yes-alt"></span>
                Import CSV - Terminé
            </h1>

            <!-- Résumé -->
            <div class="colis224-dashboard-grid colis224-grid-4">
                <div class="colis224-card colis224-card-blue">
                    <h4><span class="dashicons dashicons-media-spreadsheet"></span> Lignes Traitées</h4>
                    <p class="colis224-big-number"><?php echo $stats['total']; ?></p>
                </div>
                <div class="colis224-card colis224-card-green">
                    <h4><span class="dashicons dashicons-yes-alt"></span> Succès</h4>
                    <p class="colis224-big-number"><?php echo $stats['success']; ?></p>
                </div>
                <div class="colis224-card colis224-card-orange">
                    <h4><span class="dashicons dashicons-warning"></span> Avertissements</h4>
                    <p class="colis224-big-number"><?php echo $stats['warnings']; ?></p>
                </div>
                <div class="colis224-card colis224-card-red">
                    <h4><span class="dashicons dashicons-dismiss"></span> Erreurs</h4>
                    <p class="colis224-big-number"><?php echo $stats['errors'] + $stats['skipped']; ?></p>
                </div>
            </div>

            <!-- Détails -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📋 Rapport Détaillé</h2>

                <?php if ($stats['success'] > 0): ?>
                    <div style="background: #d1f4e0; border: 1px solid #46b450; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <p style="margin: 0; color: #0a5d2a;">
                            ✅ <strong><?php echo $stats['success']; ?> paiement(s) importé(s) avec succès !</strong>
                        </p>
                    </div>
                <?php endif; ?>

                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Ligne</th>
                            <th>Statut</th>
                            <th>N° Suivi</th>
                            <th>Messages</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['details'] as $detail): ?>
                            <tr>
                                <td><?php echo $detail['line']; ?></td>
                                <td>
                                    <?php
                                    $status_colors = array(
                                        'success' => '#46b450',
                                        'error' => '#dc3232',
                                        'skipped' => '#f0a000'
                                    );
                                    $status_labels = array(
                                        'success' => '✓ Succès',
                                        'error' => '✗ Erreur',
                                        'skipped' => '⊘ Ignoré'
                                    );
                                    ?>
                                    <span style="color: <?php echo $status_colors[$detail['status']]; ?>; font-weight: bold;">
                                        <?php echo $status_labels[$detail['status']]; ?>
                                    </span>
                                </td>
                                <td><?php echo isset($detail['tracking']) ? esc_html($detail['tracking']) : '-'; ?></td>
                                <td>
                                    <?php foreach ($detail['messages'] as $msg): ?>
                                        <span><?php echo esc_html($msg); ?></span><br>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Actions finales -->
            <div class="colis224-card" style="margin-top: 20px;">
                <a href="?page=colis224-csv-import" class="button button-primary">
                    Nouvel Import
                </a>
                <a href="?page=colis224-parcels" class="button">
                    Voir les Colis
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Traiter les actions
     */
    private static function handle_actions() {
        // Télécharger le template
        if ($_POST['action'] === 'download_template' && check_admin_referer('colis224_csv_template', 'template_nonce')) {
            $csv = Colis224_CSV_Importer::generate_template('payments');

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="template_paiements_colis224.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo $csv;
            exit;
        }

        // Upload CSV
        if ($_POST['action'] === 'upload_csv' && check_admin_referer('colis224_csv_upload', 'upload_nonce')) {
            if (empty($_FILES['csv_file'])) {
                echo '<div class="notice notice-error"><p>Aucun fichier sélectionné.</p></div>';
                return;
            }

            $validation = Colis224_CSV_Importer::validate_file($_FILES['csv_file']);

            if (!$validation['valid']) {
                echo '<div class="notice notice-error"><p>' . implode('<br>', $validation['errors']) . '</p></div>';
                return;
            }

            // Sauvegarder temporairement
            $upload_dir = wp_upload_dir();
            $temp_file = $upload_dir['basedir'] . '/colis224_import_' . time() . '.csv';

            move_uploaded_file($_FILES['csv_file']['tmp_name'], $temp_file);

            $_SESSION['colis224_csv_file'] = $temp_file;
            $_SESSION['colis224_csv_delimiter'] = $_POST['delimiter'] ?? ',';

            echo '<script>window.location.href="?page=colis224-csv-import&step=mapping";</script>';
        }

        // Sauvegarder le mapping
        if ($_POST['action'] === 'save_mapping' && check_admin_referer('colis224_csv_mapping', 'mapping_nonce')) {
            $mapping = array();

            foreach ($_POST['mapping'] as $csv_col => $db_field) {
                if (!empty($db_field)) {
                    $mapping[$csv_col] = $db_field;
                }
            }

            $_SESSION['colis224_csv_mapping'] = $mapping;

            echo '<script>window.location.href="?page=colis224-csv-import&step=preview";</script>';
        }

        // Confirmer l'import
        if ($_POST['action'] === 'confirm_import' && check_admin_referer('colis224_csv_import', 'import_nonce')) {
            echo '<script>window.location.href="?page=colis224-csv-import&step=import";</script>';
        }
    }
}
