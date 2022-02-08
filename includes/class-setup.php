<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Setup
{
    /** @var string $cache_dir */
    private $cache_dir = '/uploads/gdpress';

    /** @var string $full_cache_path */
    private $full_cache_path;

    /**
     * Set Fields
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->full_cache_path = WP_CONTENT_DIR . $this->cache_dir;

        $this->init_hooks();
    }

    /**
     * Actions and Filters
     * 
     * @return void 
     */
    private function init_hooks()
    {
        register_activation_hook(GDPRESS_PLUGIN_FILE, [$this, 'create_cache_dir']);
    }

    /**
     * Create cache directory structure, if it doesn't exist yet.
     * 
     * @return void 
     */
    public function create_cache_dir()
    {
        if (!is_dir($this->full_cache_path)) {
            wp_mkdir_p($this->full_cache_path);
        }
    }
}
