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
         * Get a fresh copy from the database if $optimized_fonts is empty|null|false (on 1st run)
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
}
