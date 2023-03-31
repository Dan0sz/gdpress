<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess;

use GDPRess\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Constants {
	/**
	 * Any constants we might need throughout the plugin are initialized here.
	 * 
	 * @return void 
	 */
	public function __construct() {
		$this->init();
	}

	private function init() {
		define( 'GDPRESS_CACHE_DIR', '/uploads/gdpr-press' );
		define( 'GDPRESS_CACHE_ABSPATH', WP_CONTENT_DIR . GDPRESS_CACHE_DIR );
		define( 'GDPRESS_TEST_MODE', get_option( Settings::GDPRESS_MANAGE_SETTING_TEST_MODE, 'on' ) );
	}
}
