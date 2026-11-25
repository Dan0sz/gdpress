<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin;

use GDPRess\Helper;

class Migrate {
	
	/**
	 * Build class.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_migrate' ], 1 );
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
		}
		
		update_option( Settings::GDPRESS_OPTION_DB_VERSION, GDPRESS_DB_VERSION );
	}
	
	/**
	 * Previous versions would store (and download) this site's own files if the URL's scheme differed.
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
		
		$removed = [];
		
		foreach ( $requests as $type => $type_requests ) {
			foreach ( $type_requests as $i => $request ) {
				if ( ! Helper::is_local_request( $request['href'] ) ) {
					continue;
				}
				
				$this->delete_local_copy( $type, $request['href'] );
				
				$removed[] = $this->strip_scheme( $request['href'] );
				$removed[] = $this->strip_scheme( Helper::get_local_url( $request['href'], $type, true ) );
				
				unset( $requests[ $type ][ $i ] );
			}
			
			$requests[ $type ] = array_values( $requests[ $type ] );
		}
		
		if ( ! $removed ) {
			return;
		}
		
		$local_urls = maybe_unserialize( get_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL, [] ) );
		
		if ( is_array( $local_urls ) ) {
			foreach ( $local_urls as $type => $urls ) {
				$local_urls[ $type ] = array_values( array_filter( $urls, fn( $url ) => ! in_array( $this->strip_scheme( $url ), $removed, true ) ) );
			}
			
			update_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL, array_filter( $local_urls ) );
		}
		
		update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, array_filter( $requests ) );
	}
	
	/**
	 * Delete the copy of $url, including the font files downloaded along with it, if it's a stylesheet.
	 *
	 * @param string $type
	 * @param string $url
	 *
	 * @return void
	 */
	private function delete_local_copy( $type, $url ) {
		$file = Helper::get_local_path( $url, $type );
		
		if ( ! $file || ! is_file( $file ) ) {
			return;
		}
		
		if ( 'css' === $type ) {
			preg_match_all( '/url\([\'"]?(?P<urls>.+?)[\'"]?\)/', (string) file_get_contents( $file ), $fonts );
			
			foreach ( $fonts['urls'] as $font ) {
				// Absolute URLs might be in use by an external stylesheet too.
				if ( str_starts_with( $font, '//' ) || preg_match( '/^[a-zA-Z][a-zA-Z0-9+.\-]*:/', $font ) ) {
					continue;
				}
				
				$font = preg_replace( '/[?#].*$/', '', $font );
				
				$this->delete_file( ( str_starts_with( $font, '/' ) ? GDPRESS_CACHE_ABSPATH . '/css' : dirname( $file ) . '/' ) . $font );
			}
		}
		
		$this->delete_file( $file );
	}
	
	/**
	 * Delete $file, and the directories it leaves empty, if it's in the cache directory.
	 *
	 * @param string $file
	 *
	 * @return void
	 */
	private function delete_file( $file ) {
		$file      = realpath( $file );
		$cache_dir = realpath( GDPRESS_CACHE_ABSPATH );
		
		if ( ! $file || ! $cache_dir || ! is_file( $file ) || ! str_starts_with( $file, $cache_dir . DIRECTORY_SEPARATOR ) ) {
			return;
		}
		
		wp_delete_file( $file );
		
		$dir = dirname( $file );
		
		while ( str_starts_with( $dir, $cache_dir . DIRECTORY_SEPARATOR ) && is_dir( $dir ) && ! array_diff( (array) scandir( $dir ), [ '.', '..' ] ) ) {
			if ( ! @rmdir( $dir ) ) {
				break;
			}
			
			$dir = dirname( $dir );
		}
	}
	
	/**
	 * @param string $url
	 *
	 * @return string
	 */
	private function strip_scheme( $url ) {
		return preg_replace( '/^https?:/', '', $url );
	}
}
