<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress
{
    /**
     * Set Fields.
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Start Plugin
     * 
     * @return void 
     */
    private function init()
    {
        $this->define_constants();
        $this->setup();

        if (is_admin()) {
            $this->render_admin_area();
        }

        if (!is_admin()) {
            add_action('init', [$this, 'rewrite_urls']);
        }

        add_filter('pre_update_option_' . Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS, [$this, 'base64_decode_value']);
    }

    /**
     * Any constants we might require to e.g. access settings in a consistent manner.
     * 
     * @return void 
     */
    private function define_constants()
    {
        define('GDPRESS_CACHE_DIR', '/uploads/gdpr-press');
        define('GDPRESS_CACHE_ABSPATH', WP_CONTENT_DIR . GDPRESS_CACHE_DIR);
        define('GDPRESS_TEST_MODE', get_option(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_TEST_MODE, 'on'));
    }

    /**
     * Takes care of all initial setup this plugin requires.
     * 
     * @return void 
     */
    private function setup()
    {
        new Gdpress_Setup();
    }

    /**
     * Renders the Admin screens.
     * 
     * @return void 
     */
    private function render_admin_area()
    {
        new Gdpress_Admin_Settings();
    }

    /**
     * Initiate URL rewriting in frontend.
     * 
     * @return void 
     */
    public function rewrite_urls()
    {
        new Gdpress_RewriteUrl();
    }

    /**
     * @since v1.2.0 gdpress_external_requests is base64_encoded in the frontend, to bypass firewall restrictions on
     * some servers, as well as prevent offset errors while unserializing.
     * 
     * @param $value
     *
     * @return string
     */
    public function base64_decode_value($value)
    {
        if (is_string($value) && base64_decode($value, true)) {
            return base64_decode($value);
        }

        return $value;
    }


    /**
     * Contains all fetched external requests.
     * 
     * @return array 
     */
    public static function requests($raw = false)
    {
        static $requests;

        /**
         * Get a fresh copy from the database if $requests is empty|null|false (on 1st run)
         */
        if (empty($requests) && $raw == false) {
            $requests = get_option(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS, []) ?: [];
        } elseif (empty($requests)) {
            $requests = get_option(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS);
        }

        /**
         * get_option() should take care of this, but sometimes it doesn't.
         * 
         * @since v0.1
         */
        if (is_string($requests)) {
            $requests = unserialize($requests);
        }

        return $requests;
    }

    /**
     * Contains all URLs marked as excluded.
     * 
     * @return array 
     */
    public static function excluded()
    {
        static $excluded;

        /**
         * Get a fresh copy from the database if $excluded is empty|null|false (on 1st run)
         */
        if (empty($excluded)) {
            $excluded = get_option(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_EXCLUDED, []) ?: [];
        }

        /**
         * get_option() should take care of this, but sometimes it doesn't.
         * 
         * @since v0.1
         */
        if (is_string($excluded)) {
            $excluded = unserialize($excluded);
        }

        return $excluded;
    }

    /**
     * Contains all localized file URLs.
     * 
     * @return array 
     */
    public static function local()
    {
        static $local;

        /**
         * Get a fresh copy from the database if $excluded is empty|null|false (on 1st run)
         */
        if (empty($local)) {
            $local = get_option(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_LOCAL, []) ?: [];
        }

        /**
         * get_option() should take care of this, but often it doesn't.
         * 
         * @since v0.1
         */
        if (is_string($local)) {
            $local = unserialize($local);
        }

        return $local;
    }

    /**
     * Check if $url is marked as excluded.
     * 
     * @param mixed $type 
     * @param mixed $url 
     * @return bool 
     */
    public static function is_excluded($type, $url)
    {
        return isset(Gdpress::excluded()[$type]) && in_array($url, Gdpress::excluded()[$type]);
    }

    /**
     * 
     * @param string $type 
     * @param string $url 
     * @param bool $write_to_db 
     * @return bool 
     */
    public static function set_local_url($type = '', $url = '', $write_to_db = false)
    {
        static $local_urls;

        if ($type && $url) {
            $local_urls[$type][] = $url;
        }

        if ($write_to_db) {
            return update_option(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_LOCAL, $local_urls);
        }

        return true;
    }

    /**
     * Generate a local path from $url.
     * 
     * @param string $url 
     * @param string $type
     *  
     * @return string 
     */
    public static function get_local_path($url, $type)
    {
        return GDPRESS_CACHE_ABSPATH . "/$type" . parse_url($url)['path'];
    }

    /**
     * Generates a local path for Google Fonts.
     * 
     * @since v1.1.0
     * 
     * @param mixed $filename 
     * @return string 
     */
    public static function get_local_path_google_font($filename)
    {
        return GDPRESS_CACHE_ABSPATH . "/css/$filename/google-fonts.css";
    }

    /**
     * Generates a local URL from $url.
     * 
     * @param string $url
     * @param string $type
     * @param bool $bypass We can force returning the URL (even when the file doesn't exist) by setting this to true.
     * 
     * @return string 
     */
    public static function get_local_url($url, $type, $bypass = false)
    {
        if (!file_exists(self::get_local_path($url, $type)) && !$bypass) {
            return '';
        }

        return content_url(GDPRESS_CACHE_DIR . "/$type" . parse_url($url)['path']);
    }

    /**
     * Generate a local URL from $filename.
     * 
     * @since v1.1.0
     * 
     * @param mixed $filename 
     * @param bool $bypass Force returning the URL even when the file doesn't exist.
     * 
     * @return string 
     */
    public static function get_local_url_google_font($filename, $bypass = false)
    {
        if (!file_exists(self::get_local_path_google_font($filename)) && !$bypass) {
            return '';
        }

        return content_url(GDPRESS_CACHE_DIR . "/css/$filename/google-fonts.css");
    }

    /**
     * Check if $url is Google Fonts API request.
     * 
     * @since v1.1.0
     * 
     * @param mixed $url 
     * @return bool 
     */
    public static function is_google_fonts_request($url)
    {
        return strpos($url, 'fonts.googleapis.com/css') !== false
            || strpos($url, 'fonts.googleapis.com/icon') !== false
            || strpos($url, 'fonts.gstatic.com') !== false;
    }
}
