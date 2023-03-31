<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin;

defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * Set fields.
	 * 
	 * @return void 
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Filters & Hooks.
	 * 
	 * @return void 
	 */
	private function init() {
		add_action( 'wp_ajax_gdpress_fetch', [ $this, 'fetch' ] );
		add_action( 'wp_ajax_gdpress_flush', [ $this, 'flush' ] );
	}

	/**
	 * Basic logic for fetching external resources.
	 * 
	 * @return void 
	 */
	public function fetch() {
		check_ajax_referer( Settings::GDPRESS_ADMIN_PAGE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "Sorry, you're not allowed to do this.", 'gdpr-press' ) );
		}

		/**
		 * Trigger a request to the frontend, so the 'template_redirect' action is initiated.
		 * 
		 * We're adding the gdpress parameter to ensure proper execution.
		 * 
		 * @see RewriteUrl::init()
		 */
		$site_url = get_home_url() . '?gdpress';
		$response = wp_remote_get( $site_url, [ 'timeout' => 60 ] );

		if ( is_wp_error( $response ) ) {
			/**
			 * Dies eventually, so no need to return.
			 * 
			 * @var WP_Error $response
			 */
			wp_send_json_error( $response->get_error_code() . ': ' . $response->get_error_message() );
		}

		wp_send_json_success();
	}

	/**
	 * Flush cache directory and remove DB settings.
	 * 
	 * @return void 
	 */
	public function flush() {
		check_ajax_referer( Settings::GDPRESS_ADMIN_PAGE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "Sorry, you're not allowed to do this.", 'gdpr-press' ) );
		}

		try {
			$entries = array_filter( (array) glob( GDPRESS_CACHE_ABSPATH . '/*' ) );

			foreach ( $entries as $entry ) {
				$this->delete( $entry );
			}

			$db_cleanup = [
				Settings::GDPRESS_MANAGE_SETTING_REQUESTS,
				Settings::GDPRESS_MANAGE_SETTING_LOCAL,
				Settings::GDPRESS_MANAGE_SETTING_EXCLUDED,
			];

			foreach ( $db_cleanup as $option ) {
				delete_option( $option );
			}

			Notice::set_notice( __( 'GDPRess Bot has successfully cleared its cache.', 'gdpr-press' ) );
		} catch ( \Exception $e ) {
			Notice::set_notice(
				__( 'GDPRess Bot could not empty the cache directory: ', 'gdpr-press' ) . $e->getMessage(),
				'error',
				'all',
				'gdpress-cache-flush-error'
			);
		}
	}

	/**
	 * @param $entry
	 */
	private function delete( $entry ) {
		if ( is_dir( $entry ) ) {
			$file = new \FilesystemIterator( $entry );

			// If dir is empty, valid() returns false.
			while ( $file->valid() ) {
				$this->delete( $file->getPathName() );
				$file->next();
			}

			rmdir( $entry );
		} else {
			unlink( $entry );
		}
	}
}
