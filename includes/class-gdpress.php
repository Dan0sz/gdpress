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
    }

    /**
     * Any constants we might require to e.g. access settings in a consistent manner.
     * 
     * @return void 
     */
    private function define_constants()
    {
        define('GDPRESS_CACHE_DIR', '/uploads/gdpress');
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
     * Contains all fetched external requests.
     * 
     * @return array 
     */
    public static function requests()
    {
        static $requests;

        /**
         * Get a fresh copy from the database if $requests is empty|null|false (on 1st run)
         */
        if (empty($requests)) {
            $requests = get_option(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS, []) ?: [];
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
     * Contains all URLs marked as excluded.
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
     * Gets the local url for $filename
     * 
     * @param string $type 
     * @param string $filename 
     * @param bool $decode
     * 
     * @return string 
     */
    public static function get_local_url($type, $filename, $decode = false)
    {
        if (!isset(self::local()[$type])) {
            return '';
        }

        foreach (self::local()[$type] as $local_url) {
            if (strpos($local_url, $filename) !== false) {
                return $decode ? urldecode($local_url) : $local_url;
            }
        }

        return '';
    }
}
