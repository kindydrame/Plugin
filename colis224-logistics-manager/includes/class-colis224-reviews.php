<?php
/**
 * MODULE 21: Système d'Évaluation et Avis Clients
 * Permet aux clients de laisser des avis après retrait de colis
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Reviews {

    public function __construct() {
        // Hook pour afficher le formulaire d'avis
        add_action('template_redirect', array($this, 'handle_feedback_request'));

        // AJAX handlers
        add_action('wp_ajax_colis224_submit_review', array($this, 'ajax_submit_review'));
        add_action('wp_ajax_nopriv_colis224_submit_review', array($this, 'ajax_submit_review'));
    }

    /**
     * Créer les tables pour les avis
     */
    public static function create_review_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des avis
        $table_reviews = $wpdb->prefix . 'colis224_reviews';
        $sql_reviews = "CREATE TABLE $table_reviews (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            client_id bigint(20) UNSIGNED DEFAULT NULL,
            driver_id bigint(20) UNSIGNED DEFAULT NULL,
            overall_rating tinyint(1) DEFAULT 5,
            delivery_rating tinyint(1) DEFAULT 5,
            service_rating tinyint(1) DEFAULT 5,
            packaging_rating tinyint(1) DEFAULT 5,
            comment text DEFAULT NULL,
            would_recommend tinyint(1) DEFAULT 1,
            client_name varchar(255) DEFAULT NULL,
            client_email varchar(255) DEFAULT NULL,
            is_verified tinyint(1) DEFAULT 1,
            is_public tinyint(1) DEFAULT 1,
            admin_response text DEFAULT NULL,
            responded_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parcel_id (parcel_id),
            KEY client_id (client_id),
            KEY driver_id (driver_id),
            KEY overall_rating (overall_rating),
            KEY is_public (is_public)
        ) $charset_collate;";
        dbDelta($sql_reviews);
    }

    /**
     * Gérer la demande de feedback
     */
    public function handle_feedback_request() {
        if (!isset($_GET['colis224_feedback'])) {
            return;
        }

        $parcel_id = isset($_GET['parcel']) ? intval($_GET['parcel']) : 0;

        if (!$parcel_id) {
            return;
        }

        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            return;
        }

        // Vérifier si avis déjà laissé
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_reviews WHERE parcel_id = %d",
            $parcel_id
        ));

        // Enqueue styles
        wp_enqueue_style('dashicons');

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Votre Avis - Colis224</title>
            <?php wp_head(); ?>
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                    margin: 0;
                    padding: 20px;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .feedback-container {
                    max-width: 600px;
                    width: 100%;
                    background: white;
                    border-radius: 15px;
                    padding: 40px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                }
                .feedback-header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .feedback-header h1 {
                    color: #667eea;
                    margin: 0;
                    font-size: 32px;
                }
                .feedback-header p {
                    color: #666;
                    margin: 10px 0 0 0;
                }
                .parcel-info {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                    text-align: center;
                }
                .parcel-info strong {
                    font-size: 18px;
                    color: #667eea;
                }
                .rating-group {
                    margin-bottom: 25px;
                }
                .rating-group label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 10px;
                    color: #333;
                }
                .star-rating {
                    display: flex;
                    gap: 10px;
                    font-size: 32px;
                    justify-content: center;
                }
                .star {
                    cursor: pointer;
                    color: #ddd;
                    transition: all 0.2s;
                }
                .star:hover,
                .star.active {
                    color: #ffc107;
                    transform: scale(1.1);
                }
                textarea {
                    width: 100%;
                    padding: 15px;
                    border: 2px solid #e0e0e0;
                    border-radius: 8px;
                    font-size: 14px;
                    resize: vertical;
                    min-height: 120px;
                    font-family: inherit;
                }
                textarea:focus {
                    border-color: #667eea;
                    outline: none;
                }
                .recommend-group {
                    text-align: center;
                    margin: 25px 0;
                }
                .recommend-group label {
                    font-weight: 600;
                    color: #333;
                    display: block;
                    margin-bottom: 15px;
                }
                .recommend-buttons {
                    display: flex;
                    gap: 15px;
                    justify-content: center;
                }
                .recommend-button {
                    flex: 1;
                    padding: 15px;
                    border: 2px solid #e0e0e0;
                    background: white;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s;
                    font-size: 16px;
                }
                .recommend-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                }
                .recommend-button.active {
                    border-color: #667eea;
                    background: #667eea;
                    color: white;
                }
                .submit-button {
                    width: 100%;
                    padding: 15px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-size: 18px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                .submit-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
                }
                .success-message {
                    text-align: center;
                    padding: 40px;
                }
                .success-message h2 {
                    color: #28a745;
                    font-size: 28px;
                    margin: 20px 0;
                }
                .success-icon {
                    font-size: 64px;
                    color: #28a745;
                }
            </style>
        </head>
        <body>
            <div class="feedback-container">
                <?php if ($existing > 0): ?>
                    <div class="success-message">
                        <div class="success-icon">✅</div>
                        <h2>Merci!</h2>
                        <p>Vous avez déjà laissé un avis pour ce colis.</p>
                    </div>
                <?php else: ?>
                    <div class="feedback-header">
                        <h1>📦 Votre Avis Compte!</h1>
                        <p>Aidez-nous à améliorer notre service</p>
                    </div>

                    <div class="parcel-info">
                        <p>Colis</p>
                        <strong><?php echo esc_html($parcel->tracking_number); ?></strong>
                    </div>

                    <form id="feedback-form">
                        <input type="hidden" name="parcel_id" value="<?php echo $parcel->id; ?>">

                        <div class="rating-group">
                            <label>Note Globale</label>
                            <div class="star-rating" data-rating="overall_rating">
                                <span class="star active" data-value="1">⭐</span>
                                <span class="star active" data-value="2">⭐</span>
                                <span class="star active" data-value="3">⭐</span>
                                <span class="star active" data-value="4">⭐</span>
                                <span class="star active" data-value="5">⭐</span>
                            </div>
                            <input type="hidden" name="overall_rating" value="5">
                        </div>

                        <div class="rating-group">
                            <label>Qualité de la Livraison</label>
                            <div class="star-rating" data-rating="delivery_rating">
                                <span class="star active" data-value="1">⭐</span>
                                <span class="star active" data-value="2">⭐</span>
                                <span class="star active" data-value="3">⭐</span>
                                <span class="star active" data-value="4">⭐</span>
                                <span class="star active" data-value="5">⭐</span>
                            </div>
                            <input type="hidden" name="delivery_rating" value="5">
                        </div>

                        <div class="rating-group">
                            <label>Qualité du Service</label>
                            <div class="star-rating" data-rating="service_rating">
                                <span class="star active" data-value="1">⭐</span>
                                <span class="star active" data-value="2">⭐</span>
                                <span class="star active" data-value="3">⭐</span>
                                <span class="star active" data-value="4">⭐</span>
                                <span class="star active" data-value="5">⭐</span>
                            </div>
                            <input type="hidden" name="service_rating" value="5">
                        </div>

                        <div class="rating-group">
                            <label>État de l'Emballage</label>
                            <div class="star-rating" data-rating="packaging_rating">
                                <span class="star active" data-value="1">⭐</span>
                                <span class="star active" data-value="2">⭐</span>
                                <span class="star active" data-value="3">⭐</span>
                                <span class="star active" data-value="4">⭐</span>
                                <span class="star active" data-value="5">⭐</span>
                            </div>
                            <input type="hidden" name="packaging_rating" value="5">
                        </div>

                        <div class="rating-group">
                            <label>Votre Commentaire (optionnel)</label>
                            <textarea name="comment" placeholder="Partagez votre expérience avec nous..."></textarea>
                        </div>

                        <div class="recommend-group">
                            <label>Recommanderiez-vous nos services?</label>
                            <div class="recommend-buttons">
                                <button type="button" class="recommend-button active" data-value="1">
                                    👍 Oui
                                </button>
                                <button type="button" class="recommend-button" data-value="0">
                                    👎 Non
                                </button>
                            </div>
                            <input type="hidden" name="would_recommend" value="1">
                        </div>

                        <button type="submit" class="submit-button">
                            ✉️ Envoyer mon Avis
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <script>
                // Gestion des étoiles
                document.querySelectorAll('.star-rating').forEach(function(ratingContainer) {
                    const stars = ratingContainer.querySelectorAll('.star');
                    const ratingName = ratingContainer.getAttribute('data-rating');
                    const input = document.querySelector('input[name="' + ratingName + '"]');

                    stars.forEach(function(star, index) {
                        star.addEventListener('click', function() {
                            const value = parseInt(this.getAttribute('data-value'));
                            input.value = value;

                            stars.forEach(function(s, i) {
                                if (i < value) {
                                    s.classList.add('active');
                                } else {
                                    s.classList.remove('active');
                                }
                            });
                        });
                    });
                });

                // Gestion des boutons recommandation
                document.querySelectorAll('.recommend-button').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        document.querySelector('input[name="would_recommend"]').value = value;

                        document.querySelectorAll('.recommend-button').forEach(function(btn) {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');
                    });
                });

                // Soumission du formulaire
                document.getElementById('feedback-form').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    formData.append('action', 'colis224_submit_review');

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelector('.feedback-container').innerHTML = `
                                <div class="success-message">
                                    <div class="success-icon">✅</div>
                                    <h2>Merci!</h2>
                                    <p>Votre avis a été enregistré avec succès.</p>
                                    <p>Nous apprécions votre retour et travaillons constamment à améliorer nos services.</p>
                                </div>
                            `;
                        } else {
                            alert('Une erreur est survenue. Veuillez réessayer.');
                        }
                    });
                });
            </script>

            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * AJAX: Soumettre un avis
     */
    public function ajax_submit_review() {
        global $wpdb;
        $table_reviews = $wpdb->prefix . 'colis224_reviews';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $parcel_id = intval($_POST['parcel_id']);

        // Récupérer le colis
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            wp_send_json_error('Colis introuvable');
            return;
        }

        // Vérifier si avis déjà laissé
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_reviews WHERE parcel_id = %d",
            $parcel_id
        ));

        if ($existing > 0) {
            wp_send_json_error('Avis déjà soumis');
            return;
        }

        // Récupérer les infos client si disponibles
        $client_name = '';
        $client_email = '';

        if ($parcel->client_id) {
            $table_clients = $wpdb->prefix . 'colis224_clients';
            $client = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_clients WHERE id = %d",
                $parcel->client_id
            ));

            if ($client) {
                $client_name = $client->name;
                $client_email = $client->email;
            }
        }

        // Enregistrer l'avis
        $wpdb->insert($table_reviews, array(
            'parcel_id' => $parcel_id,
            'client_id' => $parcel->client_id,
            'driver_id' => $parcel->driver_id,
            'overall_rating' => intval($_POST['overall_rating']),
            'delivery_rating' => intval($_POST['delivery_rating']),
            'service_rating' => intval($_POST['service_rating']),
            'packaging_rating' => intval($_POST['packaging_rating']),
            'comment' => sanitize_textarea_field(wp_unslash($_POST['comment'])),
            'would_recommend' => intval($_POST['would_recommend']),
            'client_name' => $client_name,
            'client_email' => $client_email,
            'is_verified' => 1,
            'is_public' => 1
        ));

        // Envoyer notification à l'admin
        $admin_email = get_option('admin_email');
        $subject = "Nouvel avis client - Colis224";
        $message = "Un nouveau avis a été laissé pour le colis {$parcel->tracking_number}\n\n";
        $message .= "Note globale: " . intval($_POST['overall_rating']) . "/5\n";
        $message .= "Commentaire: " . sanitize_textarea_field(wp_unslash($_POST['comment'])) . "\n";

        wp_mail($admin_email, $subject, $message);

        wp_send_json_success('Avis enregistré');
    }

    /**
     * Obtenir les statistiques des avis
     */
    public static function get_review_stats() {
        global $wpdb;
        $table_reviews = $wpdb->prefix . 'colis224_reviews';

        $stats = array();

        // Note moyenne globale
        $stats['avg_overall'] = round($wpdb->get_var(
            "SELECT AVG(overall_rating) FROM $table_reviews"
        ), 1);

        // Note moyenne livraison
        $stats['avg_delivery'] = round($wpdb->get_var(
            "SELECT AVG(delivery_rating) FROM $table_reviews"
        ), 1);

        // Note moyenne service
        $stats['avg_service'] = round($wpdb->get_var(
            "SELECT AVG(service_rating) FROM $table_reviews"
        ), 1);

        // Note moyenne emballage
        $stats['avg_packaging'] = round($wpdb->get_var(
            "SELECT AVG(packaging_rating) FROM $table_reviews"
        ), 1);

        // Nombre total d'avis
        $stats['total_reviews'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_reviews"
        );

        // Pourcentage de recommandation
        $recommends = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_reviews WHERE would_recommend = 1"
        );
        $stats['recommend_percentage'] = $stats['total_reviews'] > 0
            ? round(($recommends / $stats['total_reviews']) * 100)
            : 0;

        return $stats;
    }

    /**
     * Obtenir les avis récents
     */
    public static function get_recent_reviews($limit = 10) {
        global $wpdb;
        $table_reviews = $wpdb->prefix . 'colis224_reviews';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.tracking_number
             FROM $table_reviews r
             LEFT JOIN {$wpdb->prefix}colis224_parcels p ON r.parcel_id = p.id
             WHERE r.is_public = 1
             ORDER BY r.created_at DESC
             LIMIT %d",
            $limit
        ));
    }
}

// Initialiser
new Colis224_Reviews();
