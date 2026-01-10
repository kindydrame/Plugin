<?php
/**
 * Widget WordPress pour afficher les départs
 *
 * @package Colis224
 * @subpackage Widgets
 * @since 2.10.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Departures_Widget extends WP_Widget {

    /**
     * Constructeur du widget
     */
    public function __construct() {
        parent::__construct(
            'colis224_departures_widget',
            '🚀 Colis224 - Prochains Départs',
            array(
                'description' => 'Affiche les prochains départs (Avion/Bateau) avec réservation WhatsApp',
                'classname' => 'colis224-departures-widget'
            )
        );
    }

    /**
     * Affichage du widget
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $transport_type = !empty($instance['transport_type']) ? $instance['transport_type'] : '';
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        $show_price = isset($instance['show_price']) ? $instance['show_price'] : true;

        // Récupérer les départs
        $filters = array();
        if ($transport_type) {
            $filters['transport_type'] = $transport_type;
        }

        $departures = Colis224_Departures::get_departures($filters);
        $departures = array_slice($departures, 0, $limit);
        $countries = Colis224_Departures::get_countries();

        if (empty($departures)) {
            echo '<div class="widget-no-departures">📭 Aucun départ programmé</div>';
        } else {
            echo '<div class="colis224-widget-departures">';

            foreach ($departures as $dep) {
                $transport_icon = $dep->transport_type === 'plane' ? '✈️' : '🚢';
                $departure_flag = isset($countries[$dep->departure_country_code]) ? $countries[$dep->departure_country_code]['flag'] : '🏳️';
                $arrival_flag = isset($countries[$dep->arrival_country_code]) ? $countries[$dep->arrival_country_code]['flag'] : '🏳️';

                $departure_datetime = new DateTime($dep->departure_date);
                $formatted_date = $departure_datetime->format('d/m/Y');

                $whatsapp_message = urlencode("Bonjour, je souhaite réserver pour le départ:\n📍 {$dep->departure_city} → {$dep->arrival_city}\n📅 {$formatted_date}\n{$transport_icon} Transport");
                $whatsapp_url = "https://wa.me/{$dep->whatsapp_number}?text={$whatsapp_message}";

                ?>
                <div class="widget-departure-item">
                    <div class="widget-departure-header">
                        <span class="widget-transport-icon"><?php echo $transport_icon; ?></span>
                        <span class="widget-departure-date"><?php echo $formatted_date; ?></span>
                    </div>

                    <div class="widget-departure-route">
                        <div class="widget-route-point">
                            <?php echo $departure_flag; ?>
                            <strong><?php echo esc_html($dep->departure_city); ?></strong>
                        </div>
                        <div class="widget-route-arrow">→</div>
                        <div class="widget-route-point">
                            <?php echo $arrival_flag; ?>
                            <strong><?php echo esc_html($dep->arrival_city); ?></strong>
                        </div>
                    </div>

                    <?php if ($show_price && $dep->price_estimate && $dep->price_estimate > 0): ?>
                        <div class="widget-departure-price">
                            💰 <?php echo number_format($dep->price_estimate, 0, ',', ' ') . ' ' . $dep->currency; ?>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" class="widget-btn-whatsapp">
                        📱 Réserver
                    </a>
                </div>
                <?php
            }

            echo '</div>';
        }

        echo $args['after_widget'];
    }

    /**
     * Formulaire de configuration du widget
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '🚀 Prochains Départs';
        $transport_type = !empty($instance['transport_type']) ? $instance['transport_type'] : '';
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        $show_price = isset($instance['show_price']) ? $instance['show_price'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Titre:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('transport_type')); ?>">Type de transport:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('transport_type')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('transport_type')); ?>">
                <option value="" <?php selected($transport_type, ''); ?>>Tous</option>
                <option value="plane" <?php selected($transport_type, 'plane'); ?>>✈️ Avion uniquement</option>
                <option value="boat" <?php selected($transport_type, 'boat'); ?>>🚢 Bateau uniquement</option>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">Nombre de départs à afficher:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('limit')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number"
                   value="<?php echo esc_attr($limit); ?>" min="1" max="20">
        </p>

        <p>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_price')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_price')); ?>"
                   <?php checked($show_price, true); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_price')); ?>">Afficher les prix</label>
        </p>
        <?php
    }

    /**
     * Sauvegarder les paramètres du widget
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['transport_type'] = (!empty($new_instance['transport_type'])) ? sanitize_text_field($new_instance['transport_type']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 5;
        $instance['show_price'] = isset($new_instance['show_price']);

        return $instance;
    }
}

// Enregistrer le widget
function colis224_register_departures_widget() {
    register_widget('Colis224_Departures_Widget');
}
add_action('widgets_init', 'colis224_register_departures_widget');
