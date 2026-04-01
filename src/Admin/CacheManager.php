<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin;

use GDPRess\Helper;
use GDPRess\Download as DownloadHelper;

class CacheManager {
	
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
	 *
	 * @throws \SodiumException
	 */
	public function __construct() {
		$this->settings_page    = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$this->settings_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : Settings::GDPRESS_ADMIN_SECTION_MANAGE;
		$this->settings_updated = isset( $_GET['settings-updated'] );
		
		$this->maybe_manage_cached_files();
	}
	
	/**
	 * Filters & Hooks.
	 *
	 * @return void
	 *
	 * @throws \SodiumException
	 */
	private function maybe_manage_cached_files() {
		if ( Settings::GDPRESS_ADMIN_PAGE !== $this->settings_page ) {
			return;
		}
		
		if ( Settings::GDPRESS_ADMIN_SECTION_MANAGE !== $this->settings_tab ) {
			return;
		}
		
		if ( ! $this->settings_updated ) {
			return;
		}
		
		$this->manage_cached_files();
		
		// Clear the default 'Settings saved.' message.
		delete_transient( 'settings_errors' );
		
		// Set our own.
		add_settings_error( 'general', 'settings_updated', __( 'Selected files downloaded successfully.', 'gdpr-press' ), 'success' );
	}
	
	/**
	 * Download all not excluded files. We don't have to check if $requests actually exist because the Submit button is only made available when it does.
	 *
	 * @return void
	 *
	 * @throws \SodiumException
	 */
	private function manage_cached_files() {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		
		$downloader = new DownloadHelper();
		
		foreach ( Helper::requests() as $type => $requests ) {
			foreach ( $requests as $request ) {
				if ( $this->maybe_delete_excluded_file( $type, $request ) ) {
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
	
	/**
	 * Deletes the local file for the excluded resource, if it exists.
	 *
	 * @param string $type
	 * @param array  $request
	 *
	 * @return bool
	 */
	private function maybe_delete_excluded_file( $type, $request ) {
		if ( ! Helper::is_excluded( $type, $request['href'] ) ) {
			return false;
		}
		
		if ( Helper::is_google_fonts_request( $request['href'] ) ) {
			$file_path = Helper::get_local_path_google_font( $request['name'] );
		} else {
			$file_path = Helper::get_local_path( $request['href'], $type );
		}
		
		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}
		
		return true;
	}
}
