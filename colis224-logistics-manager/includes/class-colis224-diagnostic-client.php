<?php
/**
 * Outil de diagnostic pour l'espace client
 * À utiliser temporairement pour identifier le problème de connexion
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Diagnostic_Client {

    public function __construct() {
        // Ajouter un shortcode de diagnostic
        add_shortcode('colis224_diagnostic', array($this, 'diagnostic_shortcode'));

        // Ajouter des logs détaillés dans la console JavaScript
        add_action('wp_footer', array($this, 'add_debug_logs'));
    }

    /**
     * Shortcode de diagnostic [colis224_diagnostic]
     */
    public function diagnostic_shortcode() {
        ob_start();
        ?>
        <div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border: 2px solid #333; font-family: monospace;">
            <h2 style="color: #d63638;">🔍 DIAGNOSTIC ESPACE CLIENT COLIS224</h2>

            <?php
            // Démarrer la session si pas démarrée
            if (!session_id()) {
                session_start();
            }
            ?>

            <h3>1️⃣ Configuration PHP</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Session Support:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ ACTIVE' : '❌ INACTIVE'; ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Session ID:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo session_id() ?: '❌ Aucun'; ?></td>
                </tr>
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Session Save Path:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo session_save_path(); ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Cookie Support:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo ini_get('session.use_cookies') ? '✅ OUI' : '❌ NON'; ?></td>
                </tr>
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>HTTPS:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo is_ssl() ? '✅ OUI' : '⚠️ NON'; ?></td>
                </tr>
            </table>

            <h3>2️⃣ État de la Session Colis224</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Client ID:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo isset($_SESSION['colis224_client_id']) ? '✅ ' . $_SESSION['colis224_client_id'] : '❌ Non défini'; ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Client Name:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo isset($_SESSION['colis224_client_name']) ? '✅ ' . $_SESSION['colis224_client_name'] : '❌ Non défini'; ?></td>
                </tr>
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Client Phone:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo isset($_SESSION['colis224_client_phone']) ? '✅ ' . $_SESSION['colis224_client_phone'] : '❌ Non défini'; ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Login Time:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo isset($_SESSION['colis224_login_time']) ? '✅ ' . date('Y-m-d H:i:s', $_SESSION['colis224_login_time']) : '❌ Non défini'; ?></td>
                </tr>
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>is_client_logged_in():</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;">
                        <?php
                        if (class_exists('Colis224_Client_Auth')) {
                            echo Colis224_Client_Auth::is_client_logged_in() ? '✅ TRUE' : '❌ FALSE';
                        } else {
                            echo '❌ Classe non trouvée';
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <h3>3️⃣ Cookies</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Cookie Colis224:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;">
                        <?php
                        if (isset($_COOKIE['colis224_client_session'])) {
                            echo '✅ Présent';
                            $cookie_data = json_decode(base64_decode($_COOKIE['colis224_client_session']), true);
                            if ($cookie_data) {
                                echo '<br>Client ID: ' . ($cookie_data['client_id'] ?? 'N/A');
                                echo '<br>Login Time: ' . (isset($cookie_data['login_time']) ? date('Y-m-d H:i:s', $cookie_data['login_time']) : 'N/A');
                            }
                        } else {
                            echo '❌ Absent';
                        }
                        ?>
                    </td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>COOKIEPATH:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo defined('COOKIEPATH') ? COOKIEPATH : '❌ Non défini'; ?></td>
                </tr>
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>COOKIE_DOMAIN:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '❌ Non défini'; ?></td>
                </tr>
            </table>

            <h3>4️⃣ Shortcodes et Classes</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Shortcode [colis224_client_portal]:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo shortcode_exists('colis224_client_portal') ? '✅ Enregistré' : '❌ Non enregistré'; ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Classe Colis224_Client_Auth:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo class_exists('Colis224_Client_Auth') ? '✅ Chargée' : '❌ Non chargée'; ?></td>
                </tr>
                <tr style="background: #fff;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Classe Colis224_Frontend_Portal:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo class_exists('Colis224_Frontend_Portal') ? '✅ Chargée' : '❌ Non chargée'; ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 5px; border: 1px solid #ccc;"><strong>Classe Colis224_Client_Portal_Enhanced:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ccc;"><?php echo class_exists('Colis224_Client_Portal_Enhanced') ? '✅ Chargée' : '❌ Non chargée'; ?></td>
                </tr>
            </table>

            <h3>5️⃣ Toutes les Variables $_SESSION</h3>
            <pre style="background: #fff; padding: 10px; overflow: auto; max-height: 200px; border: 1px solid #ccc;">
<?php print_r($_SESSION); ?>
            </pre>

            <h3>6️⃣ Tous les Cookies</h3>
            <pre style="background: #fff; padding: 10px; overflow: auto; max-height: 200px; border: 1px solid #ccc;">
<?php print_r($_COOKIE); ?>
            </pre>

            <h3>7️⃣ Test de Connexion</h3>
            <p><strong>Test avec le numéro +14386867692:</strong></p>
            <?php
            global $wpdb;
            $table_clients = $wpdb->prefix . 'colis224_clients';
            $client = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_clients WHERE phone = %s",
                '+14386867692'
            ));

            if ($client) {
                echo '<div style="background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; color: #155724;">';
                echo '✅ Client trouvé en base de données<br>';
                echo 'ID: ' . $client->id . '<br>';
                echo 'Nom: ' . $client->name . '<br>';
                echo 'Téléphone: ' . $client->phone . '<br>';
                echo 'Email: ' . ($client->email ?? 'N/A') . '<br>';
                echo 'Dernière connexion: ' . ($client->last_login ?? 'Jamais') . '<br>';
                echo '</div>';
            } else {
                echo '<div style="background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; color: #721c24;">';
                echo '❌ Client NON trouvé en base de données';
                echo '</div>';
            }
            ?>

            <h3>8️⃣ Actions de Debug</h3>
            <button onclick="testLogin()" style="background: #2271b1; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px;">
                🧪 Tester la Connexion AJAX
            </button>
            <button onclick="checkSession()" style="background: #50575e; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px;">
                🔍 Vérifier l'État Actuel
            </button>
            <button onclick="clearAll()" style="background: #d63638; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px;">
                🗑️ Nettoyer Session + Cookie
            </button>

            <div id="debug-result" style="margin-top: 20px; padding: 10px; background: #fff; border: 1px solid #ccc; min-height: 50px;"></div>

            <script>
            function testLogin() {
                var resultDiv = document.getElementById('debug-result');
                resultDiv.innerHTML = '⏳ Test en cours...';

                jQuery.ajax({
                    url: colis224Frontend.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'colis224_client_login',
                        nonce: colis224Frontend.nonce,
                        client_phone: '+14386867692',
                        client_password: '',
                        redirect_url: window.location.href
                    },
                    success: function(response) {
                        console.log('Réponse complète:', response);
                        resultDiv.innerHTML = '<strong>✅ Réponse reçue:</strong><pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    },
                    error: function(xhr, status, error) {
                        console.error('Erreur:', xhr.responseText);
                        resultDiv.innerHTML = '<strong>❌ Erreur:</strong><pre>' + xhr.responseText + '</pre>';
                    }
                });
            }

            function checkSession() {
                var resultDiv = document.getElementById('debug-result');
                resultDiv.innerHTML = '⏳ Vérification...';
                location.reload();
            }

            function clearAll() {
                if (confirm('Êtes-vous sûr de vouloir nettoyer la session et les cookies ?')) {
                    // Supprimer le cookie
                    document.cookie = 'colis224_client_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                    alert('✅ Cookie supprimé. Rechargement de la page...');
                    location.reload();
                }
            }
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Ajouter des logs de debug dans la console
     */
    public function add_debug_logs() {
        if (!is_page() && !is_front_page()) {
            return;
        }

        if (!session_id()) {
            session_start();
        }

        $is_logged_in = class_exists('Colis224_Client_Auth') ? Colis224_Client_Auth::is_client_logged_in() : false;
        $has_session = isset($_SESSION['colis224_client_id']);
        $has_cookie = isset($_COOKIE['colis224_client_session']);

        ?>
        <script>
        console.log('%c🔍 COLIS224 DEBUG INFO', 'background: #222; color: #00ff00; font-size: 16px; padding: 5px;');
        console.log('Session Active:', <?php echo session_status() === PHP_SESSION_ACTIVE ? 'true' : 'false'; ?>);
        console.log('Session ID:', '<?php echo session_id(); ?>');
        console.log('Client Logged In:', <?php echo $is_logged_in ? 'true' : 'false'; ?>);
        console.log('Has Session Data:', <?php echo $has_session ? 'true' : 'false'; ?>);
        console.log('Has Cookie:', <?php echo $has_cookie ? 'true' : 'false'; ?>);
        console.log('Session Data:', <?php echo json_encode($_SESSION); ?>);
        console.log('Cookies:', <?php echo json_encode($_COOKIE); ?>);
        </script>
        <?php
    }
}

// Initialiser
new Colis224_Diagnostic_Client();
