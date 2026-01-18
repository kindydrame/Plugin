<?php
/**
 * Activation automatique de NimbaSMS
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Activator_NimbaSMS {

    /**
     * Activer NimbaSMS au chargement du plugin
     */
    public static function activate() {
        // Activer NimbaSMS par défaut si aucun provider n'est configuré
        $current_provider = get_option('colis224_sms_provider');

        if (empty($current_provider) || $current_provider === 'none') {
            // Activer NimbaSMS
            update_option('colis224_sms_provider', 'nimbasms');

            // Configurer les identifiants
            update_option('colis224_nimbasms_sid', '48782ece605fcd66fc15da242cc0142c');
            update_option('colis224_nimbasms_token', 'Basic NDg3ODJlY2U2MDVmY2Q2NmZjMTVkYTI0MmNjMDE0MmM6elhMZGIySU4xVVh2eE5KZWdyakJkX0VGMlE2XzRtemFGM2FFTlE1NW94ZmYyS0lWX1lMNi1KdnE1TUNaRGV0Vm9wc1JVaXYyNkdIUkhpYWVPbmdMU2xnQnNPOS1YNXE0dWkxS1BEUFFOMjQ=');
            update_option('colis224_nimbasms_from', 'Colis224');

            // Activer les notifications SMS
            update_option('colis224_sms_notifications', '1');

            // Marquer comme configuré
            update_option('colis224_nimbasms_configured', '1');
        }
    }

    /**
     * Vérifier et créer les dossiers d'upload
     */
    public static function check_upload_directories() {
        $upload_dir = wp_upload_dir();
        $colis224_dir = $upload_dir['basedir'] . '/colis224';

        $directories = array(
            $colis224_dir,
            $colis224_dir . '/receipts',
            $colis224_dir . '/photos',
        );

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);

                // Ajouter un fichier .htaccess pour la sécurité
                $htaccess_file = $dir . '/.htaccess';
                if (!file_exists($htaccess_file)) {
                    $htaccess_content = "Options -Indexes\n";
                    $htaccess_content .= "<Files *.php>\n";
                    $htaccess_content .= "deny from all\n";
                    $htaccess_content .= "</Files>\n";
                    file_put_contents($htaccess_file, $htaccess_content);
                }

                // Ajouter un fichier index.php vide
                $index_file = $dir . '/index.php';
                if (!file_exists($index_file)) {
                    file_put_contents($index_file, '<?php // Silence is golden');
                }
            }
        }

        return true;
    }

    /**
     * Vérifier les permissions des dossiers
     */
    public static function check_permissions() {
        $upload_dir = wp_upload_dir();
        $colis224_dir = $upload_dir['basedir'] . '/colis224';

        $directories = array(
            $colis224_dir,
            $colis224_dir . '/receipts',
            $colis224_dir . '/photos',
        );

        $errors = array();

        foreach ($directories as $dir) {
            if (!is_writable($dir)) {
                $errors[] = $dir . ' n\'est pas accessible en écriture';
            }
        }

        return $errors;
    }

    /**
     * Afficher un message admin si des problèmes sont détectés
     */
    public static function admin_notices() {
        $permissions_errors = self::check_permissions();

        if (!empty($permissions_errors)) {
            ?>
            <div class="notice notice-error">
                <p><strong>⚠️ Colis224 - Problème de permissions</strong></p>
                <ul>
                    <?php foreach ($permissions_errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>Veuillez corriger les permissions des dossiers pour permettre l'upload de fichiers.</p>
            </div>
            <?php
        }

        // Afficher un message de confirmation si NimbaSMS vient d'être activé
        if (get_option('colis224_nimbasms_configured') === '1' && !get_option('colis224_nimbasms_notice_dismissed')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>✅ NimbaSMS activé avec succès !</strong></p>
                <p>Les notifications SMS sont maintenant disponibles. Vous pouvez envoyer des SMS via:</p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>💬 Messages préenregistrés</li>
                    <li>📞 Relances rapides depuis les notifications</li>
                    <li>📧 Système de notifications automatiques</li>
                </ul>
            </div>
            <?php
            update_option('colis224_nimbasms_notice_dismissed', '1');
        }
    }
}

// Hooks
add_action('admin_init', array('Colis224_Activator_NimbaSMS', 'activate'));
add_action('admin_init', array('Colis224_Activator_NimbaSMS', 'check_upload_directories'));
add_action('admin_notices', array('Colis224_Activator_NimbaSMS', 'admin_notices'));
