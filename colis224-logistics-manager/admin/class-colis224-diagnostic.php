<?php
/**
 * Page de Diagnostic Colis224
 * Accessible via Admin WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Diagnostic {

    public static function display_page() {
        global $wpdb;
        
        $errors = 0;
        $warnings = 0;
        
        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-admin-tools"></span>
                Diagnostic Système Colis224
            </h1>
            
            <style>
                .diagnostic-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .diagnostic-test { padding: 10px; margin: 8px 0; border-left: 4px solid #ddd; background: #f9f9f9; }
                .diagnostic-ok { border-left-color: #00a32a; }
                .diagnostic-error { border-left-color: #d63638; }
                .diagnostic-warning { border-left-color: #dba617; }
                .status-ok { color: #00a32a; font-weight: bold; }
                .status-error { color: #d63638; font-weight: bold; }
                .status-warning { color: #dba617; font-weight: bold; }
                .diagnostic-pre { background: #f0f0f0; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 13px; }
            </style>
            
            <!-- Test 1: Plugin Status -->
            <div class="diagnostic-section">
                <h2>✓ Statut du Plugin</h2>
                <?php
                if (is_plugin_active('colis224-logistics-manager/colis224-logistics-manager.php')) {
                    echo '<div class="diagnostic-test diagnostic-ok"><span class="status-ok">✓ Plugin activé</span></div>';
                } else {
                    echo '<div class="diagnostic-test diagnostic-error"><span class="status-error">✗ Plugin NON activé</span></div>';
                    $errors++;
                }
                
                // Version
                $version = defined('COLIS224_VERSION') ? COLIS224_VERSION : 'Non définie';
                echo '<div class="diagnostic-test"><strong>Version:</strong> ' . esc_html($version) . '</div>';
                ?>
            </div>
            
            <!-- Test 2: Fichiers Essentiels -->
            <div class="diagnostic-section">
                <h2>📁 Fichiers Essentiels</h2>
                <?php
                $plugin_dir = COLIS224_PLUGIN_DIR;
                
                $files = array(
                    'colis224-logistics-manager.php' => 'Fichier principal',
                    'includes/class-colis224-client-auth.php' => '🆕 Classe Authentification (NOUVEAU)',
                    'includes/class-colis224-frontend-portal.php' => 'Portail Frontend',
                    'includes/class-colis224-client-portal-enhanced.php' => 'Portail Client Amélioré',
                    'assets/css/admin-style.css' => 'CSS Admin',
                    'assets/css/frontend-style.css' => 'CSS Frontend',
                    'assets/js/admin-script.js' => 'JavaScript Admin',
                    'assets/js/frontend-script.js' => 'JavaScript Frontend',
                );
                
                foreach ($files as $file => $desc) {
                    $path = $plugin_dir . $file;
                    if (file_exists($path)) {
                        $size = filesize($path);
                        $perms = substr(sprintf('%o', fileperms($path)), -4);
                        echo '<div class="diagnostic-test diagnostic-ok">';
                        echo '<span class="status-ok">✓</span> ' . esc_html($desc) . '<br>';
                        echo '<small>Taille: ' . number_format($size) . ' octets | Permissions: ' . $perms . '</small>';
                        echo '</div>';
                    } else {
                        echo '<div class="diagnostic-test diagnostic-error">';
                        echo '<span class="status-error">✗</span> ' . esc_html($desc) . ' <strong>MANQUANT</strong>';
                        echo '</div>';
                        $errors++;
                    }
                }
                ?>
            </div>
            
            <!-- Test 3: Classes PHP -->
            <div class="diagnostic-section">
                <h2>🔧 Classes PHP Chargées</h2>
                <?php
                $classes = array(
                    'Colis224_Client_Auth' => '🆕 Classe d\'authentification (NOUVELLE)',
                    'Colis224_Frontend_Portal' => 'Portail Frontend',
                    'Colis224_Client_Portal_Enhanced' => 'Portail Client Amélioré',
                    'Colis224_Database' => 'Base de données',
                    'Colis224_Sanitizer' => 'Sanitizer',
                );
                
                foreach ($classes as $class => $desc) {
                    if (class_exists($class)) {
                        echo '<div class="diagnostic-test diagnostic-ok">';
                        echo '<span class="status-ok">✓</span> ' . esc_html($desc);
                        echo '</div>';
                    } else {
                        echo '<div class="diagnostic-test diagnostic-error">';
                        echo '<span class="status-error">✗</span> ' . esc_html($desc) . ' <strong>NON CHARGÉE</strong>';
                        echo '</div>';
                        $errors++;
                    }
                }
                ?>
            </div>
            
            <!-- Test 4: Tables de Base de Données -->
            <div class="diagnostic-section">
                <h2>🗄️ Tables Base de Données</h2>
                <?php
                $tables = array(
                    'colis224_clients' => 'Clients',
                    'colis224_parcels' => 'Colis',
                    'colis224_countries' => 'Pays',
                    'colis224_transport_modes' => 'Modes de transport',
                );
                
                foreach ($tables as $table => $desc) {
                    $table_name = $wpdb->prefix . $table;
                    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
                    
                    if ($exists) {
                        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                        echo '<div class="diagnostic-test diagnostic-ok">';
                        echo '<span class="status-ok">✓</span> Table ' . esc_html($desc) . ' (' . number_format($count) . ' enregistrements)';
                        echo '</div>';
                    } else {
                        echo '<div class="diagnostic-test diagnostic-error">';
                        echo '<span class="status-error">✗</span> Table ' . esc_html($desc) . ' <strong>MANQUANTE</strong>';
                        echo '</div>';
                        $errors++;
                    }
                }
                ?>
            </div>
            
            <!-- Test 5: Clients de Test -->
            <div class="diagnostic-section">
                <h2>👥 Clients Disponibles</h2>
                <?php
                $client_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}colis224_clients");
                
                if ($client_count > 0) {
                    echo '<div class="diagnostic-test diagnostic-ok">';
                    echo '<span class="status-ok">✓</span> ' . number_format($client_count) . ' clients dans la base de données';
                    echo '</div>';
                    
                    // Afficher quelques clients pour test
                    $clients = $wpdb->get_results("SELECT id, name, phone, email FROM {$wpdb->prefix}colis224_clients LIMIT 5");
                    echo '<div class="diagnostic-pre">';
                    echo '<strong>Clients disponibles pour test de connexion:</strong><br><br>';
                    foreach ($clients as $client) {
                        echo 'ID: ' . $client->id . ' | Nom: ' . esc_html($client->name) . ' | Tél: ' . esc_html($client->phone);
                        if ($client->email) {
                            echo ' | Email: ' . esc_html($client->email);
                        }
                        echo '<br>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="diagnostic-test diagnostic-error">';
                    echo '<span class="status-error">✗</span> Aucun client dans la base de données';
                    echo '<br><strong>Action requise:</strong> Créer au moins un client dans Colis224 → Clients';
                    echo '</div>';
                    $errors++;
                }
                ?>
            </div>
            
            <!-- Test 6: Configuration PHP -->
            <div class="diagnostic-section">
                <h2>⚙️ Configuration PHP</h2>
                <?php
                echo '<div class="diagnostic-test">';
                echo '<strong>Version PHP:</strong> ' . phpversion();
                if (version_compare(phpversion(), '7.4', '>=')) {
                    echo ' <span class="status-ok">✓</span>';
                } else {
                    echo ' <span class="status-warning">⚠</span> (Minimum recommandé: 7.4)';
                    $warnings++;
                }
                echo '</div>';
                
                echo '<div class="diagnostic-test">';
                echo '<strong>Session PHP:</strong> ';
                if (session_status() === PHP_SESSION_ACTIVE) {
                    echo 'Active <span class="status-ok">✓</span>';
                    echo ' (Session ID: ' . substr(session_id(), 0, 10) . '...)';
                } else if (session_status() === PHP_SESSION_DISABLED) {
                    echo '<span class="status-error">✗ Désactivée</span>';
                    $errors++;
                } else {
                    echo 'Non démarrée <span class="status-warning">⚠</span>';
                    $warnings++;
                }
                echo '</div>';
                
                echo '<div class="diagnostic-test">';
                echo '<strong>WP_DEBUG:</strong> ' . (WP_DEBUG ? 'Activé' : 'Désactivé');
                echo '</div>';
                
                echo '<div class="diagnostic-test">';
                echo '<strong>Mémoire PHP:</strong> ' . ini_get('memory_limit');
                echo '</div>';
                ?>
            </div>
            
            <!-- Test 7: AJAX Endpoints -->
            <div class="diagnostic-section">
                <h2>🔌 Endpoints AJAX</h2>
                <?php
                $ajax_actions = array(
                    'colis224_client_login' => 'Connexion client',
                    'colis224_track_parcel' => 'Suivi de colis',
                );
                
                foreach ($ajax_actions as $action => $desc) {
                    if (has_action('wp_ajax_nopriv_' . $action) || has_action('wp_ajax_' . $action)) {
                        echo '<div class="diagnostic-test diagnostic-ok">';
                        echo '<span class="status-ok">✓</span> ' . esc_html($desc) . ' (' . $action . ')';
                        echo '</div>';
                    } else {
                        echo '<div class="diagnostic-test diagnostic-error">';
                        echo '<span class="status-error">✗</span> ' . esc_html($desc) . ' (' . $action . ') <strong>NON ENREGISTRÉ</strong>';
                        echo '</div>';
                        $errors++;
                    }
                }
                ?>
            </div>
            
            <!-- Test 8: Authentification -->
            <div class="diagnostic-section">
                <h2>🔐 Système d'Authentification</h2>
                <?php
                if (class_exists('Colis224_Client_Auth')) {
                    echo '<div class="diagnostic-test diagnostic-ok">';
                    echo '<span class="status-ok">✓</span> Classe d\'authentification chargée';
                    echo '</div>';
                    
                    // Tester les méthodes
                    $methods = array('is_client_logged_in', 'get_current_client_id', 'authenticate_by_phone');
                    foreach ($methods as $method) {
                        if (method_exists('Colis224_Client_Auth', $method)) {
                            echo '<div class="diagnostic-test diagnostic-ok">';
                            echo '<span class="status-ok">✓</span> Méthode: ' . $method . '()';
                            echo '</div>';
                        } else {
                            echo '<div class="diagnostic-test diagnostic-error">';
                            echo '<span class="status-error">✗</span> Méthode: ' . $method . '() manquante';
                            echo '</div>';
                            $errors++;
                        }
                    }
                } else {
                    echo '<div class="diagnostic-test diagnostic-error">';
                    echo '<span class="status-error">✗</span> Classe d\'authentification NON CHARGÉE';
                    echo '<br><strong>Cause probable:</strong> Fichier class-colis224-client-auth.php manquant';
                    echo '</div>';
                    $errors++;
                }
                ?>
            </div>
            
            <!-- Résumé -->
            <div class="diagnostic-section" style="background: <?php echo ($errors > 0 ? '#ffeeee' : '#eeffee'); ?>; border: 3px solid <?php echo ($errors > 0 ? '#d63638' : '#00a32a'); ?>;">
                <h2>📊 Résumé du Diagnostic</h2>
                
                <?php if ($errors === 0 && $warnings === 0): ?>
                    <div style="font-size: 18px; padding: 20px;">
                        <span class="status-ok" style="font-size: 24px;">✓✓✓ TOUT EST OK !</span>
                        <p>Le plugin est correctement configuré et devrait fonctionner normalement.</p>
                        <p><strong>Si vous avez toujours des problèmes de connexion:</strong></p>
                        <ul style="line-height: 1.8;">
                            <li>Videz le cache de votre navigateur (<code>Ctrl+F5</code>)</li>
                            <li>Videz le cache WordPress (si vous utilisez un plugin de cache)</li>
                            <li>Testez en navigation privée</li>
                            <li>Vérifiez la console JavaScript (F12) pour des erreurs</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div style="font-size: 18px; padding: 20px;">
                        <span class="status-error" style="font-size: 24px;">✗ <?php echo $errors; ?> Erreur(s) Trouvée(s)</span>
                        <?php if ($warnings > 0): ?>
                            <br><span class="status-warning" style="font-size: 20px;">⚠ <?php echo $warnings; ?> Avertissement(s)</span>
                        <?php endif; ?>
                        
                        <h3>🔧 Actions à Effectuer (par ordre de priorité):</h3>
                        <ol style="line-height: 2;">
                            <?php
                            if (!class_exists('Colis224_Client_Auth')) {
                                echo '<li><strong>UPLOADER le fichier:</strong> <code>includes/class-colis224-client-auth.php</code></li>';
                            }
                            
                            if (!file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-client-auth.php')) {
                                echo '<li><strong>Le fichier class-colis224-client-auth.php est MANQUANT</strong> - C\'est la cause principale !</li>';
                            }
                            
                            if ($client_count === 0) {
                                echo '<li><strong>CRÉER au moins un client:</strong> Aller dans Colis224 → Clients → Nouveau Client</li>';
                            }
                            
                            echo '<li><strong>VIDER tous les caches</strong> (WordPress + navigateur)</li>';
                            echo '<li><strong>DÉSACTIVER puis RÉACTIVER</strong> le plugin</li>';
                            ?>
                        </ol>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Debug Log -->
            <?php
            $debug_log = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($debug_log) && filesize($debug_log) > 0):
            ?>
            <div class="diagnostic-section">
                <h2>🐛 Dernières Erreurs (debug.log)</h2>
                <div class="diagnostic-pre" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    $log_content = file_get_contents($debug_log);
                    $lines = explode("\n", $log_content);
                    $recent_lines = array_slice($lines, -30);
                    echo esc_html(implode("\n", $recent_lines));
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Informations Système -->
            <div class="diagnostic-section">
                <h2>ℹ️ Informations Système</h2>
                <div class="diagnostic-pre">
                    <strong>Date/Heure:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                    <strong>URL du Site:</strong> <?php echo home_url(); ?><br>
                    <strong>URL Admin:</strong> <?php echo admin_url(); ?><br>
                    <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?><br>
                    <strong>Plugin Directory:</strong> <?php echo COLIS224_PLUGIN_DIR; ?><br>
                    <strong>Plugin URL:</strong> <?php echo COLIS224_PLUGIN_URL; ?><br>
                </div>
            </div>
            
        </div>
        <?php
    }
}
