<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Settings extends Gdpress_Admin
{
    const GDPRESS_ADMIN_PAGE           = 'gdpr-press';
    const GDPRESS_ADMIN_SECTION_MANAGE = 'gdpress-manage';
    const GDPRESS_ADMIN_SECTION_HELP   = 'gdpress-help';
    const GDPRESS_ADMIN_CSS_HANDLE     = 'gdpress-admin-css';

    /**
     * Transients
     */
    const GDPRESS_TRANSIENT_NEWS_REEL = 'gdpress_news_reel';

    /** @var string $active_tab */
    private $active_tab = '';

    /** @var string $admin_page */
    private $admin_page = '';

    /** @var string $text_domain */
    private $text_domain = 'gdpr-press';

    /**
     * Set fields
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->active_tab = isset($_GET['tab']) ? $_GET['tab'] : self::GDPRESS_ADMIN_SECTION_MANAGE;
        $this->admin_page = isset($_GET['page']) ? $_GET['page'] : '';

        parent::__construct();

        $this->init();
    }

    /**
     * Action & Filter hooks.
     * 
     * @return void 
     */
    private function init()
    {
        // Global
        add_action('admin_menu', [$this, 'create_menu']);
        add_filter('plugin_action_links_' . plugin_basename(GDPRESS_PLUGIN_FILE), [$this, 'add_settings_link']);

        if ($this->admin_page !== self::GDPRESS_ADMIN_PAGE) {
            return;
        }

        // Scripts
        add_action('admin_head', [$this, 'enqueue_admin_assets']);

        // Footer Text
        add_filter('admin_footer_text', [$this, 'set_footer_text_left'], 99);
        add_filter('update_footer', [$this, 'set_footer_text_right'], 11);

        // Tabs
        add_action('gdpress_settings_tab', [$this, 'add_manage_tab'], 1);
        add_action('gdpress_settings_tab', [$this, 'add_help_tab'], 2);

        // Settings Screen Content
        add_action('gdpress_settings_content', [$this, 'set_content'], 1);
    }

    /**
     * Create WP menu-item
     */
    public function create_menu()
    {
        add_options_page(
            'GDPRess',
            'GDPRess',
            'manage_options',
            self::GDPRESS_ADMIN_PAGE,
            [$this, 'settings_page']
        );

        add_action('admin_init', [$this, 'register_settings']);
    }


    /**
     * Create settings page
     */
    public function settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__("You're not cool enough to access this page.", $this->text_domain));
        } ?>

        <div class="wrap">
            <h1><?php _e('GDPRess | Eliminate External Requests', $this->text_domain); ?></h1>

            <h2 class="caos-nav nav-tab-wrapper">
                <?php do_action('gdpress_settings_tab'); ?>
            </h2>

            <form id="<?= $this->active_tab; ?>-form" method="post" action="options.php?tab=<?= $this->active_tab; ?>">
                <?php
                settings_fields($this->active_tab);
                do_settings_sections($this->active_tab); ?>

                <?php do_action('gdpress_settings_content'); ?>

                <?php
                $current_section = str_replace('-', '_', $this->active_tab);
                do_action("after_$current_section"); ?>

                <?php if ($this->active_tab !== self::GDPRESS_ADMIN_SECTION_MANAGE) : ?>
                    <?php submit_button(__('Save Changes & Download', $this->text_domain), 'primary', 'submit', false); ?>
                <?php endif; ?>
            </form>
        </div>
    <?php
    }

    /**
     * Register all settings.
     *
     * @throws ReflectionException
     */
    public function register_settings()
    {
        if (
            $this->active_tab !== self::GDPRESS_ADMIN_SECTION_MANAGE
        ) {
            $this->active_tab = self::GDPRESS_ADMIN_SECTION_MANAGE;
        }

        foreach ($this->get_settings() as $constant => $value) {
            register_setting(
                $this->active_tab,
                $value
            );
        }
    }

    /**
     * Get all settings for the current section using the constants in this class.
     *
     * @return array
     * @throws ReflectionException
     */
    private function get_settings()
    {
        $reflection = new ReflectionClass($this);
        $constants  = apply_filters('gdpress_register_settings', $reflection->getConstants());

        switch ($this->active_tab) {
            default:
                $needle = apply_filters('gdpress_register_settings_needle', 'GDPRESS_MANAGE_SETTING');
        }

        return array_filter(
            $constants,
            function ($key) use ($needle) {
                return strpos($key, $needle) !== false;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Add settings link to plugin overview
     *
     * @param $links
     *
     * @return mixed
     */
    public function add_settings_link($links)
    {
        $adminUrl     = admin_url() . 'options-general.php?page=' . self::GDPRESS_ADMIN_PAGE;
        $settingsLink = "<a href='$adminUrl'>" . __('Settings', $this->text_domain) . '</a>';
        array_push($links, $settingsLink);

        return $links;
    }
    /**
     * We add the assets directly to the head to avoid ad blockers blocking the URLs cause they include 'analytics'.
     * 
     * @return void 
     */
    public function enqueue_admin_assets()
    {
        // wp_enqueue_script(self::OMGF_ADMIN_JS_HANDLE, plugin_dir_url(OMGF_PLUGIN_FILE) . 'assets/js/omgf-admin.js', ['jquery'], OMGF_STATIC_VERSION, true);
        wp_enqueue_style(self::GDPRESS_ADMIN_CSS_HANDLE, plugin_dir_url(GDPRESS_PLUGIN_FILE) . 'assets/css/gdpress-admin.css', [], GDPRESS_STATIC_VERSION);
    }

    /**
     * Changes footer text.
     * 
     * @return string 
     */
    public function set_footer_text_left()
    {
        $text = sprintf(__('Coded with %s in The Netherlands @ <strong>FFW.Press</strong>.', $this->text_domain), '<span class="dashicons dashicons-heart ffwp-heart"></span>');

        return '<span id="footer-thankyou">' . $text . '</span>';
    }


    /**
     * All logic to generate the news reel in the bottom right of the footer on GDPRess' settings pages.
     * 
     * Includes multiple checks to make sure the reel is only shown if a recent post is available.
     * 
     * @param mixed $text 
     * @return mixed 
     */
    public function set_footer_text_right($text)
    {
        if (!extension_loaded('simplexml')) {
            return $text;
        }

        /**
         * If a WordPress update is available, show the original text.
         */
        if (strpos($text, 'Get Version') !== false) {
            return $text;
        }

        // Prevents bashing the API.
        $xml = get_transient(self::GDPRESS_TRANSIENT_NEWS_REEL);

        if (!$xml) {
            $response = wp_remote_get('https://ffw.press/blog/tag/gdpress/feed');

            if (!is_wp_error($response)) {
                $xml = wp_remote_retrieve_body($response);

                // Refresh the feed once a day to prevent bashing of the API.
                set_transient(self::GDPRESS_TRANSIENT_NEWS_REEL, $xml, DAY_IN_SECONDS);
            }
        }

        if (!$xml) {
            return $text;
        }

        /**
         * Make sure the XML is properly encoded.
         */
        $xml = utf8_encode(html_entity_decode($xml));
        $xml = simplexml_load_string($xml);

        if (!$xml) {
            return $text;
        }

        $items = $xml->channel->item ?? [];

        if (empty($items)) {
            return $text;
        }

        $text = sprintf(__('Recently tagged <a target="_blank" href="%s"><strong>#GDPRess</strong></a> on my blog:', $this->text_domain), 'https://ffw.press/blog/tag/gdpress') . ' ';
        $text .= '<span id="gdpress-ticker-wrap">';
        $i    = 0;

        foreach ($items as $item) {
            if ($i > 4) {
                break;
            }

            $hide = $i > 0 ? 'style="display: none;"' : '';
            $text .= "<span class='ticker-item' $hide>" . sprintf('<a target="_blank" href="%s"><em>%s</em></a>', $item->link, $item->title) . '</span>';
            $i++;
        }

        $text .= "</span>";

        return $text;
    }

    /**
     * Adds the Manage Tab to the settings screen.
     * 
     * @return void 
     */
    public function add_manage_tab()
    {
        $this->generate_tab(self::GDPRESS_ADMIN_SECTION_MANAGE, 'dashicons-download', __('Manage External Requests', $this->text_domain));
    }

    /**
     * Adds the Manage Tab to the settings screen.
     * 
     * @return void 
     */
    public function add_help_tab()
    {
        $this->generate_tab(self::GDPRESS_ADMIN_SECTION_HELP, 'dashicons-editor-help', __('Help & Support', $this->text_domain));
    }

    /**
     * @param      $id
     * @param null $icon
     * @param null $label
     */
    private function generate_tab($id, $icon = null, $label = null)
    {
    ?>
        <a class="nav-tab dashicons-before <?= $icon; ?> <?= $this->active_tab == $id ? 'nav-tab-active' : ''; ?>" href="<?= $this->generate_tab_link($id); ?>">
            <?= $label; ?>
        </a>
<?php
    }

    /**
     * @param $tab
     *
     * @return string
     */
    private function generate_tab_link($tab)
    {
        $admin_page = self::GDPRESS_ADMIN_PAGE;

        return admin_url("options-general.php?page=$admin_page&tab=$tab");
    }

    /**
     * Render active content.
     */
    public function set_content()
    {
        echo apply_filters(str_replace('-', '_', $this->active_tab) . '_content', '');
    }
}
