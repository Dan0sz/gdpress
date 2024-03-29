<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin;

use GDPRess\Helper;
use GDPRess\Download as DownloadHelper;

defined( 'ABSPATH' ) || exit;

class Download {

	/** @var string $settings_page */
	private $settings_page = '';

	/** @var string $settings_tab */
	private $settings_tab = '';

	/** @var bool $settings_updated */
	private $settings_updated = false;

	/**
	 * Set Fields.
	 * 
	 * @return void 
	 */
	public function __construct() {
		$this->settings_page    = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$this->settings_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : Settings::GDPRESS_ADMIN_SECTION_MANAGE;
		$this->settings_updated = isset( $_GET['settings-updated'] );

		$this->maybe_download();
	}

	/**
	 * Filters & Hooks.
	 * 
	 * @return void 
	 */
	private function maybe_download() {
		if ( Settings::GDPRESS_ADMIN_PAGE !== $this->settings_page ) {
			return;
		}

		if ( Settings::GDPRESS_ADMIN_SECTION_MANAGE !== $this->settings_tab ) {
			return;
		}

		if ( ! $this->settings_updated ) {
			return;
		}

		$this->download();

		// Clear default 'Settings saved.' message.
		delete_transient( 'settings_errors' );

		// Set our own.
		add_settings_error( 'general', 'settings_updated', __( 'Selected files downloaded successfully.', 'gdpr-press' ), 'success' );
	}

	/**
	 * Download all not excluded files. We don't have to check if $requests actually exists, because 
	 * the submit button is only made available when it does.
	 * 
	 * @return void 
	 */
	private function download() {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$downloader = new DownloadHelper();

		foreach ( Helper::requests() as $type => $requests ) {
			foreach ( $requests as $request ) {
				if ( Helper::is_excluded( $type, $request['href'] ) ) {
					continue;
				}

				$url = $downloader->download_file( $request['name'], $type, $request['href'] );

				Helper::set_local_url( $type, $url );
			}
		}

		/**
		 * Write everything to the database.
		 */
		Helper::set_local_url( '', '', true );
	}
}
