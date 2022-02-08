<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin
{
    /**
     * Set fields.
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Hooks and Filters
     * 
     * @return void 
     */
    private function init()
    {
        add_action('admin_notices', [$this, 'print_notices']);

        $this->build_manage_section();
        $this->build_help_section();
    }

    /**
     * Print onscreen notices, if any.
     * 
     * @return void 
     */
    public function print_notices()
    {
        Gdpress_Admin_Notice::print_notices();
    }

    /**
     * Build Manage section contents.
     * 
     * @return void 
     */
    private function build_manage_section()
    {
        new Gdpress_Admin_Settings_Manage();
    }

    /**
     * Load Help section contents.
     * 
     * @return void 
     */
    private function build_help_section()
    {
        new Gdpress_Admin_Settings_Help();
    }
}
