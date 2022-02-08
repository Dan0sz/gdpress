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
        add_action('admin_init', [$this, 'download_files']);

        $this->add_ajax_hooks();
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

    public function download_files()
    {
    }

    /**
     * Add AJAX hooks.
     * 
     * @return void 
     */
    private function add_ajax_hooks()
    {
        new Gdpress_Admin_Ajax();
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
