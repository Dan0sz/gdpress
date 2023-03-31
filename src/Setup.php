<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */
namespace GDPRess;

defined( 'ABSPATH' ) || exit;

class Setup {

	/**
	 * Set Fields
	 * 
	 * @return void 
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Actions and Filters
	 * 
	 * @return void 
	 */
	private function init_hooks() {
		register_activation_hook( GDPRESS_PLUGIN_FILE, [ $this, 'create_cache_dir' ] );
	}

	/**
	 * Create cache directory structure, if it doesn't exist yet.
	 * 
	 * @return void 
	 */
	public function create_cache_dir() {
		if ( ! is_dir( GDPRESS_CACHE_ABSPATH ) ) {
			wp_mkdir_p( GDPRESS_CACHE_ABSPATH );
		}
	}
}
