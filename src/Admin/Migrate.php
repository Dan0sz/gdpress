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
		add_action( 'init', [ $this, 'maybe_migrate' ], 1 );
		add_action( self::DELETE_OLD_FILES_HOOK, [ $this, 'delete_old_files' ] );
	}
	
	/**
	 * Run the migrations this install hasn't had yet. This isn't limited to the admin, because after an automatic update
	 * it can take a while before someone logs in.
	 *
	 * @action init
	 *
	 * @return void
	 */
	public function maybe_migrate() {
		$db_version = get_option( Settings::GDPRESS_OPTION_DB_VERSION, '0' );
		
		if ( version_compare( $db_version, GDPRESS_DB_VERSION, '>=' ) ) {
			return;
		}
		
		if ( version_compare( $db_version, '1.3.2', '<' ) ) {
			$this->merge_requests();
			$this->copy_local_files();
			$this->remove_local_requests();
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
	 * Previous versions didn't store local copies by host. These are copied instead of moved, because cached pages might
	 * still refer to them.
	 *
	 * @see    Migrate::schedule_delete_old_files()
	 *
	 * @since v1.3.2
	 *
	 * @return void
	 */
	private function copy_local_files() {
		$requests = maybe_unserialize( Helper::requests( true ) );
		
		if ( ! is_array( $requests ) ) {
			return;
		}
		
		foreach ( $requests as $type => $type_requests ) {
			$files = [];
			
			foreach ( $type_requests as $request ) {
				$new = Helper::is_google_fonts_request( $request['href'] ) ? Helper::get_local_path_google_font( $request['name'] ) : Helper::get_local_path( $request['href'], $type );
				$old = $this->get_old_path( $new, $type );
				
				if ( $old ) {
					$files[ $old ][ $new ] = $request['href'];
				}
			}
			
			foreach ( $files as $old => $new_files ) {
				// If the copy was shared by multiple hosts, we don't know which one it belongs to.
				if ( count( $new_files ) > 1 || ! is_file( $old ) ) {
					continue;
				}
				
				$new = key( $new_files );
				$url = current( $new_files );
				
				if ( is_file( $new ) || Helper::is_local_request( $url ) || Helper::is_excluded( $type, $url ) ) {
					continue;
				}
				
				if ( 'css' === $type ) {
					$this->copy_stylesheet( $old, $new, Helper::is_google_fonts_request( $url ) );
				} else {
					$this->copy_file( $old, $new );
				}
			}
		}
	}
	
	/**
	 * Get the path, without the host, which was used by previous versions.
	 *
	 * @param string $path
	 * @param string $type
	 *
	 * @return string
	 */
	private function get_old_path( $path, $type ) {
		$dir = wp_normalize_path( GDPRESS_CACHE_ABSPATH . "/$type/" );
		
		if ( ! $path || ! str_starts_with( $path, $dir ) ) {
			return '';
		}
		
		$rel_path = explode( '/', substr( $path, strlen( $dir ) ), 2 )[1] ?? '';
		
		return $rel_path ? $dir . $rel_path : '';
	}
	
	/**
	 * Copy a stylesheet, along with its font files.
	 *
	 * @param string $old
	 * @param string $new
	 * @param bool   $is_google_fonts
	 *
	 * @return void
	 */
	private function copy_stylesheet( $old, $new, $is_google_fonts ) {
		$contents = (string) file_get_contents( $old );
		$css_url  = content_url( GDPRESS_CACHE_DIR . '/css/' );
		
		// Ignore the host, because the stylesheet isn't updated when the site moves to another domain.
		$pattern = '#(?:https?:)?//[^/\s\'"()]+' . preg_quote( wp_parse_url( $css_url, PHP_URL_PATH ), '#' ) . '#';
		
		// The host of fonts with an absolute URL is unknown, unless it's a Google Fonts stylesheet.
		if ( ! $is_google_fonts && preg_match( $pattern, $contents ) ) {
			return;
		}
		
		$css_dir = wp_normalize_path( (string) realpath( GDPRESS_CACHE_ABSPATH . '/css' ) ) . '/';
		$host    = $is_google_fonts ? 'fonts.gstatic.com' : $this->get_host_dir( $new );
		$fonts   = [];
		
		preg_match_all( '/url\(\s*[\'"]?(?P<urls>.+?)[\'"]?\s*\)/', $contents, $matches );
		
		foreach ( array_unique( $matches['urls'] ) as $url ) {
			$font     = preg_replace( '/[?#].*$/', '', $url );
			$is_local = preg_match( $pattern, $font );
			
			if ( $is_local ) {
				$file = $css_dir . preg_replace( $pattern, '', $font );
			} elseif ( str_starts_with( $font, '/' ) || str_contains( $font, ':' ) ) {
				continue;
			} else {
				$file = dirname( $old ) . '/' . $font;
			}
			
			$file = wp_normalize_path( (string) realpath( $file ) );
			
			if ( is_file( $file ) && str_starts_with( $file, $css_dir ) ) {
				$fonts[] = substr( $file, strlen( $css_dir ) );
			} elseif ( $is_local ) {
				// Incomplete, so let it be downloaded again.
				return;
			}
		}
		
		foreach ( $fonts as $font ) {
			$this->copy_file( $css_dir . $font, "$css_dir$host/$font" );
		}
		
		wp_mkdir_p( dirname( $new ) );
		
		file_put_contents( $new, preg_replace( $pattern, "$css_url$host/", $contents ) );
	}
	
	/**
	 * Get the name of the host's directory in the path of a stylesheet.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	private function get_host_dir( $path ) {
		return explode( '/', substr( $path, strlen( wp_normalize_path( GDPRESS_CACHE_ABSPATH . '/css/' ) ) ) )[0];
	}
	
	/**
	 * @param string $old
	 * @param string $new
	 *
	 * @return void
	 */
	private function copy_file( $old, $new ) {
		wp_mkdir_p( dirname( $new ) );
		
		copy( $old, $new );
	}
	
	/**
	 * The copies stored by previous versions aren't used anymore, but cached pages might still refer to them. So, give
	 * these some time to expire.
	 *
	 * The change time is used, because the modification time isn't updated by all deployment methods.
	 *
	 * @since v1.3.2
	 *
	 * @return void
	 */
	private function schedule_delete_old_files() {
		if ( ! is_dir( GDPRESS_CACHE_ABSPATH . '/css' ) && ! is_dir( GDPRESS_CACHE_ABSPATH . '/js' ) ) {
			return;
		}
		
		wp_schedule_single_event( time() + 30 * DAY_IN_SECONDS, self::DELETE_OLD_FILES_HOOK, [ (int) filectime( GDPRESS_PLUGIN_FILE ) ] );
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
			$len = strlen( "$dir/" );
			
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			
			$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );
			
			foreach ( $files as $file ) {
				$path = $file->getPathname();
				
				// Stored by host, so it's not an old file.
				if ( preg_match( '#^([a-z0-9_-]+\.)+[a-z][a-z0-9-]*/#', wp_normalize_path( substr( $path, $len ) ) ) ) {
					continue;
				}
				
				
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
