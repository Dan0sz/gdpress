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
}
