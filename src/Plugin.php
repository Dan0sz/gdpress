<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess;

use GDPRess\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Plugin {

	/**
	 * Set Fields.
	 * 
	 * @return void 
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Start Plugin
	 * 
	 * @return void 
	 */
	private function init() {
		new Constants();
		new Setup();

		if ( is_admin() ) {
			new Settings();
		}

		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'rewrite_urls' ] );
		}

		add_filter( 'pre_update_option_' . Settings::GDPRESS_MANAGE_SETTING_REQUESTS, [ $this, 'base64_decode_value' ] );
	}

	/**
	 * Initiate URL rewriting in frontend.
	 * 
	 * @return void 
	 */
	public function rewrite_urls() {
		new RewriteUrl();
	}

	/**
	 * @since v1.2.0 gdpress_external_requests is base64_encoded in the frontend, to bypass firewall restrictions on
	 * some servers, as well as prevent offset errors while unserializing.
	 * 
	 * @filter pre_update_option_gdpress_external_requests
	 * 
	 * @param $value
	 *
	 * @return string
	 */
	public function base64_decode_value( $value ) {
		if ( is_string( $value ) && base64_decode( $value, true ) ) {
			return base64_decode( $value );
		}

		return $value;
	}
}
