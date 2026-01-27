<?php
/**
 * MODULE 27: Multi-Langues Complet
 * Support de plusieurs langues: Français, Anglais, Arabe, Chinois
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_MultiLang {

    private $current_lang;
    private $available_langs;
    private $translations;

    public function __construct() {
        $this->available_langs = array(
            'fr' => array('name' => 'Français', 'flag' => '🇫🇷'),
            'en' => array('name' => 'English', 'flag' => '🇬🇧'),
            'ar' => array('name' => 'العربية', 'flag' => '🇸🇦', 'rtl' => true),
            'zh' => array('name' => '中文', 'flag' => '🇨🇳')
        );

        // Déterminer la langue courante
        $this->current_lang = $this->get_current_language();

        // Charger les traductions
        $this->load_translations();

        // Hook pour le sélecteur de langue
        add_shortcode('colis224_language_selector', array($this, 'language_selector_shortcode'));

        // AJAX pour changer la langue
        add_action('wp_ajax_colis224_change_language', array($this, 'ajax_change_language'));
        add_action('wp_ajax_nopriv_colis224_change_language', array($this, 'ajax_change_language'));
    }

    /**
     * Créer les tables pour les traductions
     */
    public static function create_multilang_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des traductions
        $table_translations = $wpdb->prefix . 'colis224_translations';
        $sql_translations = "CREATE TABLE $table_translations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            translation_key varchar(255) NOT NULL,
            language varchar(10) NOT NULL,
            translation_value text NOT NULL,
            context varchar(100) DEFAULT 'general',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_translation (translation_key, language),
            KEY language (language),
            KEY context (context)
        ) $charset_collate;";
        dbDelta($sql_translations);

        // Créer les traductions par défaut
        self::create_default_translations();
    }

    /**
     * Créer les traductions par défaut
     */
    private static function create_default_translations() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_translations';

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $translations = array(
            // Navigation
            array('key' => 'dashboard', 'fr' => 'Tableau de bord', 'en' => 'Dashboard', 'ar' => 'لوحة التحكم', 'zh' => '仪表板'),
            array('key' => 'parcels', 'fr' => 'Colis', 'en' => 'Parcels', 'ar' => 'الطرود', 'zh' => '包裹'),
            array('key' => 'clients', 'fr' => 'Clients', 'en' => 'Clients', 'ar' => 'العملاء', 'zh' => '客户'),
            array('key' => 'tracking', 'fr' => 'Suivi', 'en' => 'Tracking', 'ar' => 'التتبع', 'zh' => '追踪'),
            array('key' => 'settings', 'fr' => 'Paramètres', 'en' => 'Settings', 'ar' => 'الإعدادات', 'zh' => '设置'),

            // États
            array('key' => 'status_pending', 'fr' => 'En attente', 'en' => 'Pending', 'ar' => 'قيد الانتظار', 'zh' => '待处理'),
            array('key' => 'status_shipped', 'fr' => 'Expédié', 'en' => 'Shipped', 'ar' => 'مشحون', 'zh' => '已发货'),
            array('key' => 'status_delivered', 'fr' => 'Livré', 'en' => 'Delivered', 'ar' => 'تم التسليم', 'zh' => '已送达'),
            array('key' => 'status_transit', 'fr' => 'En transit', 'en' => 'In transit', 'ar' => 'في الطريق', 'zh' => '运输中'),

            // Paiements
            array('key' => 'payment_paid', 'fr' => 'Payé', 'en' => 'Paid', 'ar' => 'مدفوع', 'zh' => '已付款'),
            array('key' => 'payment_pending', 'fr' => 'Non payé', 'en' => 'Unpaid', 'ar' => 'غير مدفوع', 'zh' => '未付款'),
            array('key' => 'payment_partial', 'fr' => 'Partiel', 'en' => 'Partial', 'ar' => 'جزئي', 'zh' => '部分付款'),

            // Actions
            array('key' => 'action_search', 'fr' => 'Rechercher', 'en' => 'Search', 'ar' => 'بحث', 'zh' => '搜索'),
            array('key' => 'action_save', 'fr' => 'Enregistrer', 'en' => 'Save', 'ar' => 'حفظ', 'zh' => '保存'),
            array('key' => 'action_cancel', 'fr' => 'Annuler', 'en' => 'Cancel', 'ar' => 'إلغاء', 'zh' => '取消'),
            array('key' => 'action_delete', 'fr' => 'Supprimer', 'en' => 'Delete', 'ar' => 'حذف', 'zh' => '删除'),
            array('key' => 'action_edit', 'fr' => 'Modifier', 'en' => 'Edit', 'ar' => 'تعديل', 'zh' => '编辑'),

            // Messages
            array('key' => 'msg_success', 'fr' => 'Opération réussie', 'en' => 'Operation successful', 'ar' => 'نجحت العملية', 'zh' => '操作成功'),
            array('key' => 'msg_error', 'fr' => 'Une erreur est survenue', 'en' => 'An error occurred', 'ar' => 'حدث خطأ', 'zh' => '发生错误'),
            array('key' => 'msg_welcome', 'fr' => 'Bienvenue', 'en' => 'Welcome', 'ar' => 'مرحبا', 'zh' => '欢迎'),

            // Formulaires
            array('key' => 'form_name', 'fr' => 'Nom', 'en' => 'Name', 'ar' => 'الاسم', 'zh' => '姓名'),
            array('key' => 'form_email', 'fr' => 'Email', 'en' => 'Email', 'ar' => 'البريد الإلكتروني', 'zh' => '电子邮件'),
            array('key' => 'form_phone', 'fr' => 'Téléphone', 'en' => 'Phone', 'ar' => 'الهاتف', 'zh' => '电话'),
            array('key' => 'form_address', 'fr' => 'Adresse', 'en' => 'Address', 'ar' => 'العنوان', 'zh' => '地址'),
            array('key' => 'form_tracking', 'fr' => 'N° de suivi', 'en' => 'Tracking number', 'ar' => 'رقم التتبع', 'zh' => '追踪号码'),

            // Dates et temps
            array('key' => 'time_today', 'fr' => 'Aujourd\'hui', 'en' => 'Today', 'ar' => 'اليوم', 'zh' => '今天'),
            array('key' => 'time_yesterday', 'fr' => 'Hier', 'en' => 'Yesterday', 'ar' => 'أمس', 'zh' => '昨天'),
            array('key' => 'time_week', 'fr' => 'Semaine', 'en' => 'Week', 'ar' => 'أسبوع', 'zh' => '周'),
            array('key' => 'time_month', 'fr' => 'Mois', 'en' => 'Month', 'ar' => 'شهر', 'zh' => '月'),
        );

        foreach ($translations as $trans) {
            $key = $trans['key'];
            foreach (array('fr', 'en', 'ar', 'zh') as $lang) {
                if (isset($trans[$lang])) {
                    $wpdb->insert($table, array(
                        'translation_key' => $key,
                        'language' => $lang,
                        'translation_value' => $trans[$lang],
                        'context' => 'general'
                    ));
                }
            }
        }
    }

    /**
     * Récupérer la langue courante
     */
    private function get_current_language() {
        // Vérifier la session
        if (isset($_SESSION['colis224_language'])) {
            return $_SESSION['colis224_language'];
        }

        // Vérifier le cookie
        if (isset($_COOKIE['colis224_language'])) {
            return $_COOKIE['colis224_language'];
        }

        // Détecter la langue du navigateur
        $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2);

        if (isset($this->available_langs[$browser_lang])) {
            return $browser_lang;
        }

        // Par défaut: français
        return 'fr';
    }

    /**
     * Charger les traductions
     */
    private function load_translations() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_translations';

        // Vérifier si la table existe avant de charger
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

        if (!$table_exists) {
            $this->translations = array();
            return;
        }

        $translations = $wpdb->get_results($wpdb->prepare(
            "SELECT translation_key, translation_value FROM $table WHERE language = %s",
            $this->current_lang
        ));

        $this->translations = array();
        if ($translations) {
            foreach ($translations as $trans) {
                $this->translations[$trans->translation_key] = $trans->translation_value;
            }
        }
    }

    /**
     * Traduire une clé
     */
    public function translate($key, $default = '') {
        if (isset($this->translations[$key])) {
            return $this->translations[$key];
        }

        return $default ?: $key;
    }

    /**
     * Alias de translate()
     */
    public function __($key, $default = '') {
        return $this->translate($key, $default);
    }

    /**
     * Shortcode sélecteur de langue
     */
    public function language_selector_shortcode($atts) {
        ob_start();
        ?>
        <div class="colis224-language-selector">
            <select id="language-selector">
                <?php foreach ($this->available_langs as $code => $lang): ?>
                    <option value="<?php echo $code; ?>" <?php selected($this->current_lang, $code); ?>>
                        <?php echo $lang['flag']; ?> <?php echo $lang['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <style>
            .colis224-language-selector {
                display: inline-block;
            }
            #language-selector {
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background: white;
                font-size: 14px;
                cursor: pointer;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#language-selector').on('change', function() {
                var lang = $(this).val();

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'colis224_change_language',
                        language: lang
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
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
     * AJAX: Changer de langue
     */
    public function ajax_change_language() {
        $language = sanitize_text_field($_POST['language']);

        if (!isset($this->available_langs[$language])) {
            wp_send_json_error('Langue non supportée');
            return;
        }

        // Enregistrer dans la session
        $_SESSION['colis224_language'] = $language;

        // Enregistrer dans un cookie (30 jours)
        setcookie('colis224_language', $language, time() + (30 * 24 * 60 * 60), '/');

        wp_send_json_success('Langue mise à jour');
    }

    /**
     * Obtenir la langue courante
     */
    public function get_language() {
        return $this->current_lang;
    }

    /**
     * Vérifier si la langue courante est RTL
     */
    public function is_rtl() {
        return isset($this->available_langs[$this->current_lang]['rtl'])
            && $this->available_langs[$this->current_lang]['rtl'];
    }

    /**
     * Obtenir les langues disponibles
     */
    public function get_available_languages() {
        return $this->available_langs;
    }

    /**
     * Fonction helper globale pour traduire
     */
    public static function t($key, $default = '') {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance->translate($key, $default);
    }
}

// Initialiser
$GLOBALS['colis224_multilang'] = new Colis224_MultiLang();

/**
 * Fonction globale helper pour traduction
 */
if (!function_exists('colis224_t')) {
    function colis224_t($key, $default = '') {
        return Colis224_MultiLang::t($key, $default);
    }
}
