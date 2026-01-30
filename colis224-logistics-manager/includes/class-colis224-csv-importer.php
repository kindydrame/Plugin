<?php
/**
 * Importation CSV pour paiements et colis
 *
 * @package Colis224_Logistics
 * @version 2.15.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_CSV_Importer {

    /**
     * Parser un fichier CSV
     *
     * @param string $file_path Chemin du fichier
     * @param string $delimiter Délimiteur
     * @return array|false
     */
    public static function parse_csv($file_path, $delimiter = ',') {
        if (!file_exists($file_path)) {
            return false;
        }

        $rows = array();
        $handle = fopen($file_path, 'r');

        if ($handle === false) {
            return false;
        }

        // Lire la première ligne (en-têtes)
        $headers = fgetcsv($handle, 0, $delimiter);

        if ($headers === false) {
            fclose($handle);
            return false;
        }

        // Nettoyer les en-têtes
        $headers = array_map('trim', $headers);

        // Lire les lignes de données
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }

        fclose($handle);

        return array(
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows)
        );
    }

    /**
     * Détecter automatiquement le type de colonnes
     *
     * @param array $headers En-têtes du CSV
     * @return array Mapping suggéré
     */
    public static function detect_column_mapping($headers) {
        $mapping = array();

        // Patterns de détection
        $patterns = array(
            'tracking_number' => array('suivi', 'tracking', 'numero', 'n°', 'reference', 'ref'),
            'client_name' => array('client', 'nom client', 'customer', 'destinataire'),
            'phone' => array('telephone', 'tel', 'phone', 'mobile', 'contact'),
            'amount' => array('montant', 'amount', 'total', 'prix', 'price'),
            'paid_amount' => array('paye', 'paid', 'verse', 'recu', 'encaisse'),
            'payment_method' => array('methode', 'method', 'mode', 'paiement', 'payment'),
            'payment_date' => array('date', 'date paiement', 'payment date'),
            'notes' => array('note', 'notes', 'remarque', 'comment', 'observation')
        );

        foreach ($headers as $header) {
            $header_lower = mb_strtolower($header, 'UTF-8');

            foreach ($patterns as $field => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($header_lower, $keyword) !== false) {
                        $mapping[$header] = $field;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }

    /**
     * Valider une ligne de données
     *
     * @param array $row Ligne de données
     * @param array $mapping Mapping des colonnes
     * @return array Résultat de validation
     */
    public static function validate_row($row, $mapping) {
        $errors = array();
        $warnings = array();

        // Extraire les données selon le mapping
        $data = array();
        foreach ($mapping as $csv_column => $db_field) {
            $data[$db_field] = isset($row[$csv_column]) ? trim($row[$csv_column]) : '';
        }

        // Validations obligatoires
        if (empty($data['tracking_number']) && empty($data['client_name'])) {
            $errors[] = 'N° suivi ou nom client requis';
        }

        if (!empty($data['amount']) && !is_numeric($data['amount'])) {
            $errors[] = 'Montant invalide';
        }

        if (!empty($data['paid_amount']) && !is_numeric($data['paid_amount'])) {
            $errors[] = 'Montant payé invalide';
        }

        // Avertissements
        if (empty($data['payment_method'])) {
            $warnings[] = 'Méthode de paiement non spécifiée';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'data' => $data
        );
    }

    /**
     * Importer les paiements depuis un CSV
     *
     * @param string $file_path Chemin du fichier
     * @param array $mapping Mapping des colonnes
     * @param array $options Options d'import
     * @return array Résultat de l'import
     */
    public static function import_payments($file_path, $mapping, $options = array()) {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Parser le CSV
        $csv_data = self::parse_csv($file_path, $options['delimiter'] ?? ',');

        if ($csv_data === false) {
            return array(
                'success' => false,
                'message' => 'Erreur lors de la lecture du fichier CSV.'
            );
        }

        $stats = array(
            'total' => count($csv_data['rows']),
            'success' => 0,
            'errors' => 0,
            'warnings' => 0,
            'skipped' => 0,
            'details' => array()
        );

        foreach ($csv_data['rows'] as $index => $row) {
            $line_number = $index + 2; // +2 car ligne 1 = headers, index commence à 0

            // Valider la ligne
            $validation = self::validate_row($row, $mapping);

            if (!$validation['valid']) {
                $stats['errors']++;
                $stats['details'][] = array(
                    'line' => $line_number,
                    'status' => 'error',
                    'messages' => $validation['errors']
                );
                continue;
            }

            $data = $validation['data'];

            // Rechercher le colis
            $parcel = null;

            if (!empty($data['tracking_number'])) {
                $parcel = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_parcels WHERE tracking_number = %s",
                    $data['tracking_number']
                ));
            } elseif (!empty($data['client_name'])) {
                // Rechercher par nom client
                $client = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_clients WHERE name LIKE %s LIMIT 1",
                    '%' . $wpdb->esc_like($data['client_name']) . '%'
                ));

                if ($client) {
                    $parcel = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table_parcels WHERE client_id = %d ORDER BY created_at DESC LIMIT 1",
                        $client->id
                    ));
                }
            }

            if (!$parcel) {
                $stats['skipped']++;
                $stats['details'][] = array(
                    'line' => $line_number,
                    'status' => 'skipped',
                    'messages' => array('Colis introuvable')
                );
                continue;
            }

            // Mettre à jour le paiement
            $update_data = array();

            if (!empty($data['paid_amount'])) {
                $paid_amount = floatval($data['paid_amount']);
                $update_data['paid_amount'] = $paid_amount;
                $update_data['remaining_amount'] = $parcel->total_amount - $paid_amount;

                // Mettre à jour le statut de paiement
                if ($paid_amount >= $parcel->total_amount) {
                    $update_data['payment_status'] = 'Payé';
                } elseif ($paid_amount > 0) {
                    $update_data['payment_status'] = 'Partiel';
                } else {
                    $update_data['payment_status'] = 'Impayé';
                }
            }

            if (!empty($data['payment_method'])) {
                $update_data['payment_method'] = sanitize_text_field($data['payment_method']);
            }

            if (!empty($update_data)) {
                $wpdb->update($table_parcels, $update_data, array('id' => $parcel->id));

                // Log dans l'historique
                if (class_exists('Colis224_Parcel_History')) {
                    Colis224_Parcel_History::log_payment_change(
                        $parcel->id,
                        $parcel->payment_status,
                        $update_data['payment_status'] ?? $parcel->payment_status,
                        $update_data['paid_amount'] ?? $parcel->paid_amount
                    );
                }

                $stats['success']++;

                $messages = array('Paiement importé avec succès');
                if (!empty($validation['warnings'])) {
                    $stats['warnings']++;
                    $messages = array_merge($messages, $validation['warnings']);
                }

                $stats['details'][] = array(
                    'line' => $line_number,
                    'status' => 'success',
                    'tracking' => $parcel->tracking_number,
                    'messages' => $messages
                );
            }
        }

        return array(
            'success' => true,
            'stats' => $stats
        );
    }

    /**
     * Générer un template CSV
     *
     * @param string $type Type de template (payments, parcels)
     * @return string Contenu CSV
     */
    public static function generate_template($type = 'payments') {
        $templates = array(
            'payments' => array(
                'headers' => array('N° Suivi', 'Client', 'Montant Payé', 'Méthode Paiement', 'Date Paiement', 'Notes'),
                'example' => array('PA001234', 'Jean Dupont', '50000', 'Espèces', '2024-01-15', 'Premier acompte')
            ),
            'parcels' => array(
                'headers' => array('N° Suivi', 'Client', 'Destinataire', 'Téléphone', 'Poids', 'Montant Total', 'Statut'),
                'example' => array('PA001234', 'Jean Dupont', 'Marie Martin', '+224628123456', '5.5', '100000', 'En transit')
            )
        );

        $template = $templates[$type] ?? $templates['payments'];

        $csv = implode(',', $template['headers']) . "\n";
        $csv .= implode(',', $template['example']) . "\n";

        return $csv;
    }

    /**
     * Valider le format du fichier
     *
     * @param array $file Fichier uploadé ($_FILES)
     * @return array Résultat
     */
    public static function validate_file($file) {
        $errors = array();

        // Vérifier l'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'valid' => false,
                'errors' => array('Erreur lors de l\'upload du fichier.')
            );
        }

        // Vérifier l'extension
        $allowed_extensions = array('csv', 'txt');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = 'Format de fichier non autorisé. Utilisez .csv ou .txt';
        }

        // Vérifier la taille (max 5 Mo)
        $max_size = 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            $errors[] = 'Fichier trop volumineux (max 5 Mo).';
        }

        // Vérifier le contenu
        $content = file_get_contents($file['tmp_name']);
        if (empty($content)) {
            $errors[] = 'Le fichier est vide.';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
}
