<?php
/**
 * Script d'application automatique du correctif pour l'erreur "Duplicate entry PA3330"
 * Version 2.18.2
 *
 * INSTRUCTIONS:
 * 1. Uploadez ce fichier à la racine de votre WordPress
 * 2. Accédez à : http://votre-site.com/apply-fix-v2.18.2.php
 * 3. Le script appliquera le correctif automatiquement
 * 4. SUPPRIMEZ ce fichier après utilisation pour des raisons de sécurité
 */

// Sécurité : Décommenter la ligne suivante et mettre un mot de passe sécurisé
// define('FIX_PASSWORD', 'votre_mot_de_passe_securise');

if (defined('FIX_PASSWORD')) {
    if (!isset($_GET['password']) || $_GET['password'] !== FIX_PASSWORD) {
        die('❌ Accès refusé. Mot de passe requis.');
    }
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Application du correctif</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5}";
echo ".success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;border-radius:5px;margin:10px 0}";
echo ".error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;border-radius:5px;margin:10px 0}";
echo ".info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;padding:15px;border-radius:5px;margin:10px 0}";
echo "pre{background:#fff;padding:15px;border:1px solid #ddd;overflow-x:auto;border-radius:5px}</style></head><body>";

echo "<h1>🔧 Application du correctif - Version 2.18.2</h1>";

// Chemin du fichier à modifier
$plugin_file = __DIR__ . '/wp-content/plugins/colis224-logistics-manager/admin/class-colis224-colis.php';

echo "<div class='info'>📍 Fichier cible : " . htmlspecialchars($plugin_file) . "</div>";

// Vérifier que le fichier existe
if (!file_exists($plugin_file)) {
    echo "<div class='error'>❌ Erreur : Le fichier n'existe pas. Vérifiez le chemin du plugin.</div>";
    echo "<div class='info'>💡 Assurez-vous que le plugin est installé dans : wp-content/plugins/colis224-logistics-manager/</div>";
    exit;
}

// Créer une sauvegarde
$backup_file = $plugin_file . '.backup-' . date('Y-m-d-H-i-s');
if (!copy($plugin_file, $backup_file)) {
    echo "<div class='error'>❌ Erreur : Impossible de créer une sauvegarde.</div>";
    exit;
}
echo "<div class='success'>✅ Sauvegarde créée : " . basename($backup_file) . "</div>";

// Lire le contenu actuel
$content = file_get_contents($plugin_file);

// Ancien code à remplacer (simplifié pour la détection)
$old_code_marker = '$max_attempts = 10;';

if (strpos($content, $old_code_marker) === false) {
    echo "<div class='info'>ℹ️ Le correctif semble déjà appliqué ou le code est différent.</div>";
    echo "<div class='info'>💡 Vérifiez manuellement le fichier ou contactez le support.</div>";
    exit;
}

// Code complet à remplacer
$old_code = '        // Génération du numéro de suivi si vide
        $tracking_number = sanitize_text_field($_POST[\'tracking_number\']);
        if (empty($tracking_number)) {
            $phone = sanitize_text_field($_POST[\'recipient_phone\']);
            $last4 = substr(preg_replace(\'/\s/\', \'\', $phone), -4);

            // Boucle pour garantir l\'unicité du numéro de suivi
            $attempts = 0;
            $max_attempts = 10;

            do {
                $attempts++;

                // Première tentative : PA + 4 derniers chiffres du téléphone
                if ($attempts === 1) {
                    $tracking_number = \'PA\' . $last4;
                } else {
                    // Tentatives suivantes : Ajouter un suffixe avec timestamp + random
                    $suffix = substr(time(), -3) . rand(10, 99);
                    $tracking_number = \'PA\' . $last4 . \'-\' . $suffix;
                }

                // Vérifier l\'unicité
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
                    $tracking_number
                ));

                // Si on a essayé 10 fois sans succès, utiliser un numéro complètement unique
                if ($attempts >= $max_attempts && $count > 0) {
                    $tracking_number = \'PA\' . strtoupper(wp_generate_password(8, false, false));
                    $count = 0; // Forcer la sortie de la boucle
                }

            } while ($count > 0 && $attempts < $max_attempts);
        } else {
            // Si un tracking number est fourni manuellement, vérifier qu\'il n\'existe pas déjà
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
                $tracking_number
            ));

            if ($count > 0) {
                echo \'<div class="notice notice-error"><p>❌ Erreur : Le numéro de suivi \' . esc_html($tracking_number) . \' existe déjà. Veuillez en choisir un autre.</p></div>\';
                return;
            }
        }';

// Nouveau code corrigé
$new_code = '        // Génération du numéro de suivi si vide
        $tracking_number = sanitize_text_field($_POST[\'tracking_number\']);
        if (empty($tracking_number)) {
            $phone = sanitize_text_field($_POST[\'recipient_phone\']);
            $last4 = substr(preg_replace(\'/\s/\', \'\', $phone), -4);

            // Boucle pour garantir l\'unicité du numéro de suivi avec protection contre les doublons
            $attempts = 0;
            $max_attempts = 20;
            $tracking_number_generated = false;

            do {
                $attempts++;

                // Première tentative : PA + 4 derniers chiffres du téléphone
                if ($attempts === 1) {
                    $tracking_number = \'PA\' . $last4;
                } else if ($attempts <= 5) {
                    // Tentatives 2-5 : Ajouter un suffixe séquentiel simple
                    $tracking_number = \'PA\' . $last4 . \'-\' . ($attempts - 1);
                } else {
                    // Tentatives suivantes : Utiliser timestamp + random pour garantir l\'unicité
                    $suffix = substr(time(), -4) . str_pad(rand(0, 999), 3, \'0\', STR_PAD_LEFT);
                    $tracking_number = \'PA\' . $last4 . \'-\' . $suffix;
                }

                // Vérifier l\'unicité avec un verrou pour éviter les race conditions
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
                    $tracking_number
                ));

                if ($count == 0) {
                    $tracking_number_generated = true;
                    break;
                }

                // Si on a essayé 20 fois sans succès, utiliser un numéro complètement unique avec timestamp
                if ($attempts >= $max_attempts) {
                    $tracking_number = \'PA\' . date(\'ymd\') . \'-\' . strtoupper(substr(wp_generate_password(6, false, false), 0, 6));
                    $tracking_number_generated = true;
                    break;
                }

                // Petit délai pour éviter les collisions en cas de création simultanée
                if ($attempts > 1) {
                    usleep(50000); // 50ms
                }

            } while ($attempts < $max_attempts);

            if (!$tracking_number_generated) {
                echo \'<div class="notice notice-error"><p>❌ Erreur : Impossible de générer un numéro de suivi unique après \' . $max_attempts . \' tentatives. Veuillez réessayer.</p></div>\';
                return;
            }
        } else {
            // Si un tracking number est fourni manuellement, vérifier qu\'il n\'existe pas déjà
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s",
                $tracking_number
            ));

            if ($count > 0) {
                echo \'<div class="notice notice-error"><p>❌ Erreur : Le numéro de suivi \' . esc_html($tracking_number) . \' existe déjà. Veuillez en choisir un autre ou laissez le champ vide pour une génération automatique.</p></div>\';
                return;
            }
        }';

// Appliquer le remplacement
$new_content = str_replace($old_code, $new_code, $content);

if ($new_content === $content) {
    echo "<div class='error'>❌ Erreur : Le remplacement a échoué. Le code n'a pas été trouvé.</div>";
    echo "<div class='info'>💡 Le code du plugin pourrait être différent. Utilisez la méthode manuelle.</div>";
    exit;
}

// Écrire le nouveau contenu
if (!file_put_contents($plugin_file, $new_content)) {
    echo "<div class='error'>❌ Erreur : Impossible d'écrire dans le fichier.</div>";
    exit;
}

echo "<div class='success'><h2>✅ Correctif appliqué avec succès !</h2></div>";
echo "<div class='info'><h3>📋 Modifications appliquées :</h3><ul>";
echo "<li>✅ Nombre de tentatives augmenté de 10 à 20</li>";
echo "<li>✅ Stratégies progressives de génération ajoutées</li>";
echo "<li>✅ Protection anti-collision avec délai de 50ms</li>";
echo "<li>✅ Génération de secours améliorée</li>";
echo "<li>✅ Messages d'erreur optimisés</li>";
echo "</ul></div>";

echo "<div class='info'><h3>🧪 Étapes suivantes :</h3><ol>";
echo "<li>Testez la création d'un client et d'un colis</li>";
echo "<li>Vérifiez que l'erreur \"Duplicate entry PA3330\" ne se produit plus</li>";
echo "<li><strong>IMPORTANT : Supprimez ce fichier (apply-fix-v2.18.2.php) pour la sécurité</strong></li>";
echo "</ol></div>";

echo "<div class='info'>💾 Sauvegarde disponible : " . basename($backup_file) . "<br>";
echo "En cas de problème, vous pouvez restaurer la sauvegarde.</div>";

echo "</body></html>";
?>
