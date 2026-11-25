<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin;

use GDPRess\Helper;

class Migrate {
	
	const DELETE_OLD_FILES_HOOK = 'gdpress_delete_old_files';
	
	/**
	 * Build class.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_migrate' ], 1 );
		add_action( self::DELETE_OLD_FILES_HOOK, [ $this, 'delete_old_files' ] );
	}
	
	/**
	 * Run the migrations this install hasn't had yet.
	 *
	 * @action admin_init
	 *
	 * @return void
	 */
	public function maybe_migrate() {
		$db_version = get_option( Settings::GDPRESS_OPTION_DB_VERSION, '0' );
		
		if ( version_compare( $db_version, GDPRESS_DB_VERSION, '>=' ) ) {
			return;
		}
		
		if ( version_compare( $db_version, '1.3.2', '<' ) ) {
			$this->remove_local_requests();
			$this->merge_requests();
			delete_option( 'gdpress_local_urls' ); // Not used anymore.
			$this->schedule_delete_old_files();
		}
		
		update_option( Settings::GDPRESS_OPTION_DB_VERSION, GDPRESS_DB_VERSION );
	}
	
	/**
	 * Previous versions would store this site's own files if the URL's scheme differed.
	 *
	 * @since v1.3.2
	 *
	 * @return void
	 */
	private function remove_local_requests() {
		$requests = maybe_unserialize( Helper::requests( true ) );
		
		if ( ! is_array( $requests ) ) {
			return;
		}
		
		foreach ( $requests as $type => $type_requests ) {
			$requests[ $type ] = array_values( array_filter( $type_requests, fn( $request ) => ! Helper::is_local_request( $request['href'] ) ) );
		}
		
		update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, array_filter( $requests ) );
	}
	
	/**
	 * Previous versions would store a request again for each variant of its query string.
	 *
	 * @since v1.3.2
	 *
	 * @return void
	 */
	private function merge_requests() {
		$requests = maybe_unserialize( Helper::requests( true ) );
		
		if ( ! is_array( $requests ) ) {
			return;
		}
		
		update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, array_map( [ Helper::class, 'unique_requests' ], $requests ) );
	}
	
	/**
	 * The copies stored by previous versions aren't used anymore, but cached pages might still refer to them. So, give
	 * these some time to expire.
	 *
	 * @since v1.3.2
	 *
	 * @return void
	 */
	private function schedule_delete_old_files() {
		wp_schedule_single_event( time() + 30 * DAY_IN_SECONDS, self::DELETE_OLD_FILES_HOOK, [ (int) filemtime( GDPRESS_PLUGIN_FILE ) ] );
	}
	
	/**
	 * Delete all local copies stored before $timestamp, and the directories this leaves empty.
	 *
	 * @since  v1.3.2
	 *
	 * @action gdpress_delete_old_files
	 *
	 * @param int $timestamp The moment this version was installed.
	 *
	 * @return void
	 */
	public function delete_old_files( $timestamp ) {
		foreach ( [ 'css', 'js' ] as $type ) {
			$dir = GDPRESS_CACHE_ABSPATH . "/$type";
			
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			
			$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );
			
			foreach ( $files as $file ) {
				$path = $file->getPathname();
				
				if ( $file->isDir() && ! $file->isLink() ) {
					if ( ! array_diff( (array) scandir( $path ), [ '.', '..' ] ) ) {
						@rmdir( $path );
					}
				} elseif ( $file->isFile() && $file->getMTime() < $timestamp ) {
					wp_delete_file( $path );
				}
			}
		}
	}
}
