<?php
/**
 * MODULE 25: Portail Partenaire Complet
 * Interface dédiée pour les partenaires: dashboard, soumission colis, suivi commissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Partner_Portal {

    public function __construct() {
        // Shortcodes
        add_shortcode('colis224_partner_login', array($this, 'partner_login_shortcode'));
        add_shortcode('colis224_partner_dashboard', array($this, 'partner_dashboard_shortcode'));

        // AJAX
        add_action('wp_ajax_colis224_partner_login', array($this, 'ajax_partner_login'));
        add_action('wp_ajax_nopriv_colis224_partner_login', array($this, 'ajax_partner_login'));
        add_action('wp_ajax_colis224_partner_submit_parcel', array($this, 'ajax_submit_parcel'));
    }

    /**
     * Créer les tables pour le portail partenaire
     */
    public static function create_partner_portal_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des accès partenaires
        $table_access = $wpdb->prefix . 'colis224_partner_access';
        $sql_access = "CREATE TABLE $table_access (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            partner_id bigint(20) UNSIGNED NOT NULL,
            username varchar(255) NOT NULL,
            password varchar(255) NOT NULL,
            last_login datetime DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY username (username),
            UNIQUE KEY partner_id (partner_id)
        ) $charset_collate;";
        dbDelta($sql_access);

        // Table des commissions partenaires
        $table_commissions = $wpdb->prefix . 'colis224_partner_commissions';
        $sql_commissions = "CREATE TABLE $table_commissions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            partner_id bigint(20) UNSIGNED NOT NULL,
            transaction_id bigint(20) UNSIGNED NOT NULL,
            commission_amount decimal(15,2) NOT NULL,
            commission_rate decimal(5,2) DEFAULT 0.00,
            status enum('pending','approved','paid') DEFAULT 'pending',
            payment_date date DEFAULT NULL,
            payment_reference varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY partner_id (partner_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_commissions);

        // Table des factures partenaires
        $table_invoices = $wpdb->prefix . 'colis224_partner_invoices';
        $sql_invoices = "CREATE TABLE $table_invoices (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            partner_id bigint(20) UNSIGNED NOT NULL,
            invoice_number varchar(50) NOT NULL,
            period_start date NOT NULL,
            period_end date NOT NULL,
            total_amount decimal(15,2) NOT NULL,
            commission_amount decimal(15,2) DEFAULT 0.00,
            status enum('draft','sent','paid','cancelled') DEFAULT 'draft',
            due_date date DEFAULT NULL,
            paid_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY partner_id (partner_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_invoices);
    }

    /**
     * Shortcode de connexion partenaire
     */
    public function partner_login_shortcode($atts) {
        if (isset($_SESSION['colis224_partner_id'])) {
            return '<p>Vous êtes déjà connecté. <a href="?partner_logout=1">Se déconnecter</a></p>';
        }

        // Gérer la déconnexion
        if (isset($_GET['partner_logout'])) {
            unset($_SESSION['colis224_partner_id']);
            unset($_SESSION['colis224_partner_name']);
            return '<p>Vous avez été déconnecté.</p>';
        }

        ob_start();
        ?>
        <div class="colis224-partner-login">
            <h2>Connexion Partenaire</h2>
            <form id="partner-login-form">
                <p>
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" required>
                </p>
                <p>
                    <label>Mot de passe</label>
                    <input type="password" name="password" required>
                </p>
                <p>
                    <button type="submit" class="button button-primary">Se connecter</button>
                </p>
                <div id="login-message"></div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#partner-login-form').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_partner_login',
                        username: $('input[name="username"]').val(),
                        password: $('input[name="password"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            $('#login-message').html('<div class="error">' + response.data + '</div>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Connexion partenaire
     */
    public function ajax_partner_login() {
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_partner_access';

        $access = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE username = %s AND is_active = 1",
            $username
        ));

        if ($access && wp_check_password($password, $access->password)) {
            // Récupérer le partenaire
            $table_partners = $wpdb->prefix . 'colis224_partners';
            $partner = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_partners WHERE id = %d",
                $access->partner_id
            ));

            if ($partner) {
                $_SESSION['colis224_partner_id'] = $partner->id;
                $_SESSION['colis224_partner_name'] = $partner->name;

                // Mettre à jour last_login
                $wpdb->update($table,
                    array('last_login' => current_time('mysql')),
                    array('id' => $access->id)
                );

                wp_send_json_success('Connexion réussie');
            }
        }

        wp_send_json_error('Identifiants incorrects');
    }

    /**
     * Shortcode dashboard partenaire
     */
    public function partner_dashboard_shortcode($atts) {
        if (!isset($_SESSION['colis224_partner_id'])) {
            return '<p>Veuillez vous connecter pour accéder au portail partenaire.</p>';
        }

        $partner_id = $_SESSION['colis224_partner_id'];
        $partner_name = $_SESSION['colis224_partner_name'];

        global $wpdb;
        $table_trans = $wpdb->prefix . 'colis224_partner_transactions';

        // Statistiques
        $total_confie = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_trans WHERE partner_id = %d AND transaction_type = 'confié'",
            $partner_id
        ));

        $total_recu = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_trans WHERE partner_id = %d AND transaction_type = 'reçu'",
            $partner_id
        ));

        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT balance FROM {$wpdb->prefix}colis224_partners WHERE id = %d",
            $partner_id
        ));

        ob_start();
        ?>
        <div class="colis224-partner-dashboard">
            <h1>Bienvenue, <?php echo esc_html($partner_name); ?>!</h1>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 10px; color: white;">
                    <h3>Colis Confiés</h3>
                    <p style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo $total_confie; ?></p>
                </div>

                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 25px; border-radius: 10px; color: white;">
                    <h3>Colis Reçus</h3>
                    <p style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo $total_recu; ?></p>
                </div>

                <div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); padding: 25px; border-radius: 10px; color: white;">
                    <h3>Solde</h3>
                    <p style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format($balance, 0, ',', ' '); ?> GNF</p>
                </div>
            </div>

            <h2>Actions Rapides</h2>
            <div style="margin: 20px 0;">
                <button class="button button-primary">📦 Soumettre un colis</button>
                <button class="button">📊 Voir mes transactions</button>
                <button class="button">💰 Historique des paiements</button>
            </div>

            <h2>Dernières Transactions</h2>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>N° Tracking</th>
                        <th>Montant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $transactions = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $table_trans WHERE partner_id = %d ORDER BY created_at DESC LIMIT 10",
                        $partner_id
                    ));

                    foreach ($transactions as $trans):
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($trans->created_at)); ?></td>
                            <td><?php echo esc_html($trans->transaction_type); ?></td>
                            <td><?php echo esc_html($trans->internal_tracking_number); ?></td>
                            <td><?php echo number_format($trans->amount, 0, ',', ' '); ?> GNF</td>
                            <td><?php echo esc_html($trans->parcel_status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialiser
new Colis224_Partner_Portal();
