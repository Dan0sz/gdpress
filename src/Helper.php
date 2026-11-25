<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess;

use GDPRess\Admin\Settings;

class Helper {
	/**
	 * Generates a local URL from $url.
	 *
	 * @param string $url
	 * @param string $type
	 * @param bool   $bypass We can force returning the URL (even when the file doesn't exist) by setting this to true.
	 *
	 * @return string
	 */
	public static function get_local_url( $url, $type, $bypass = false ) {
		$local_path = self::get_local_path( $url, $type );
		
		if ( ! $local_path ) {
			return '';
		}
		
		if ( ! file_exists( $local_path ) && ! $bypass ) {
			return '';
		}
		
		$rel_path = self::get_relative_path( $url );
		
		if ( ! $rel_path ) {
			return '';
		}
		
		return content_url( GDPRESS_CACHE_DIR . "/$type$rel_path" );
	}
	
	/**
	 * Generate a local path from $url.
	 *
	 * @param string $url
	 * @param string $type
	 *
	 * @return string
	 */
	public static function get_local_path( $url, $type ) {
		if ( ! isset( $url ) ) {
			return '';
		}
		
		$rel_path = self::get_relative_path( $url );
		
		if ( ! $rel_path ) {
			return '';
		}
		
		$full_path = wp_normalize_path( GDPRESS_CACHE_ABSPATH . "/$type$rel_path" );
		
		/**
		 * Collapse dot segments.
		 */
		$parts     = explode( '/', $full_path );
		$collapsed = [];
		
		foreach ( $parts as $part ) {
			if ( $part === '.' || $part === '' ) {
				continue;
			}
			
			if ( $part === '..' ) {
				array_pop( $collapsed );
			} else {
				$collapsed[] = $part;
			}
		}
		
		$full_path = ( str_starts_with( $full_path, '/' ) ? '/' : '' ) . implode( '/', $collapsed );
		
		if ( ! self::is_within_cache_dir( $full_path ) ) {
			return '';
		}
		
		return $full_path;
	}
	
	/**
	 * Generate a path from $url, relative to the cache directory. It starts with the host, because different hosts can
	 * serve different files from the same path.
	 *
	 * @since v1.3.2
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	private static function get_relative_path( $url ) {
		$parts = wp_parse_url( $url );
		
		if ( empty( $parts['host'] ) || empty( $parts['path'] ) ) {
			/**
			 * @TODO: Generate a unique path a different way.
			 */
			return '';
		}
		
		$port = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		
		return self::sanitize_relative_path( '/' . strtolower( $parts['host'] ) . $port . $parts['path'] );
	}
	
	/**
	 * Sanitizes and normalizes a relative path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	private static function sanitize_relative_path( $path ) {
		$path = wp_normalize_path( $path );
		
		if ( $path === '/' ) {
			return '/';
		}
		
		$segments = explode( '/', $path );
		$rel_path = '';
		
		foreach ( $segments as $segment ) {
			if ( empty( $segment ) || $segment === '.' || $segment === '..' ) {
				continue;
			}
			
			/**
			 * sanitize_file_name() messes up filenames of minified (.min.) files.
			 */
			$sanitized = preg_replace( '/[^a-zA-Z0-9\-_\.]/', '-', $segment );
			
			if ( str_starts_with( $sanitized, 'unnamed-file.' ) && ! str_starts_with( $segment, 'unnamed-file.' ) ) {
				$sanitized = $segment;
			}
			
			$rel_path .= '/' . basename( $sanitized );
		}
		
		return $rel_path;
	}
	
	/**
	 * Verifies if $path is within GDPRESS_CACHE_ABSPATH.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	private static function is_within_cache_dir( $path ) {
		$path           = wp_normalize_path( $path );
		$cache_dir_path = wp_normalize_path( GDPRESS_CACHE_ABSPATH );
		
		return str_starts_with( $path, $cache_dir_path );
	}
	
	/**
	 * Generate a local URL from $filename.
	 *
	 * @since v1.1.0
	 *
	 * @param mixed $filename
	 * @param bool  $bypass Force returning the URL even when the file doesn't exist.
	 *
	 * @return string
	 */
	public static function get_local_url_google_font( $filename, $bypass = false ) {
		$rel_path = self::sanitize_relative_path( "/fonts.googleapis.com/$filename/google-fonts.css" );
		
		if ( ! $rel_path ) {
			return '';
		}
		
		$local_path = self::get_local_path_google_font( $filename );
		
		if ( ! $local_path ) {
			return '';
		}
		
		if ( ! file_exists( $local_path ) && ! $bypass ) {
			return '';
		}
		
		return content_url( GDPRESS_CACHE_DIR . "/css$rel_path" );
	}
	
	/**
	 * Generates a local path for Google Fonts.
	 *
	 * @since v1.1.0
	 *
	 * @param mixed $filename
	 *
	 * @return string
	 */
	public static function get_local_path_google_font( $filename ) {
		$rel_path = self::sanitize_relative_path( "/fonts.googleapis.com/$filename/google-fonts.css" );
		
		if ( ! $rel_path ) {
			return '';
		}
		
		$full_path = GDPRESS_CACHE_ABSPATH . "/css$rel_path";
		
		if ( ! self::is_within_cache_dir( $full_path ) ) {
			return '';
		}
		
		return $full_path;
	}
	
	/**
	 * Check if $url is marked as excluded.
	 *
	 * @param mixed $type
	 * @param mixed $url
	 *
	 * @return bool
	 */
	public static function is_excluded( $type, $url ) {
		/**
		 * @var string $url is stored in the database decoded, but it's escaped in the form.
		 */
		$url         = html_entity_decode( $url );
		$is_excluded = isset( self::excluded()[ $type ] ) && in_array( self::get_request_id( $url ), array_map( [ self::class, 'get_request_id' ], self::excluded()[ $type ] ), true );
		
		if ( $is_excluded ) {
			return true;
		}
		
		foreach ( self::exclusion_list() as $pattern ) {
			if ( str_contains( $url, $pattern ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Contains all URLs marked as excluded.
	 *
	 * @return array
	 */
	public static function excluded() {
		static $excluded;
		
		/**
		 * Get a fresh copy from the database if $excluded is empty|null|false (on 1st run)
		 */
		if ( empty( $excluded ) ) {
			$excluded = get_option( Settings::GDPRESS_MANAGE_SETTING_EXCLUDED, [] ) ?: [];
		}
		
		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 *
		 * @since v0.1
		 */
		if ( is_string( $excluded ) ) {
			$excluded = maybe_unserialize( $excluded );
		}
		
		return $excluded;
	}
	
	/**
	 * Contains a list of URL patterns that are automatically marked as excluded.
	 *
	 * @since v1.4.0
	 *
	 * @return array
	 */
	public static function exclusion_list() {
		return apply_filters(
			'gdpress_exclusion_list',
			[
				'app.usercentrics.eu',
				'applepay.cdn-apple.com',
				'challenges.cloudflare.com',
				'googletagmanager.com/gtag/js',
				'google-analytics.com/analytics.js',
				'hcaptcha.com/1/api.js',
				'js.stripe.com',
				'maps.googleapis.com',
				'paypal.com/sdk/js',
			]
		);
	}
	
	/**
	 * Check if $url is Google Fonts API request.
	 *
	 * @since v1.1.0
	 *
	 * @param mixed $url
	 *
	 * @return bool
	 */
	public static function is_google_fonts_request( $url ) {
		/**
		 * Is OMGF active? If so, bail.
		 */
		return strpos( $url, 'fonts.googleapis.com/css' ) !== false
		       || strpos( $url, 'fonts.googleapis.com/icon' ) !== false
		       || strpos( $url, 'fonts.gstatic.com' ) !== false;
	}
	
	/**
	 * Check if $url is Web Font Loader script.
	 *
	 * @since v1.3.1
	 *
	 * @param mixed $url
	 *
	 * @return bool
	 */
	public static function is_webfont_loader_request( $url ) {
		return strpos( $url, 'webfont.js' ) !== false;
	}
	
	/**
	 * Contains all fetched external requests.
	 *
	 * @return array
	 */
	public static function requests( $raw = false ) {
		static $requests;
		
		if ( $raw ) {
			return get_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS );
		}
		
		/**
		 * Get a fresh copy from the database if $requests is empty|null|false (on 1st run)
		 */
		if ( empty( $requests ) ) {
			$requests = get_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, [] ) ?: [];
		}
		
		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 *
		 * @since v0.1
		 */
		if ( is_string( $requests ) ) {
			$requests = maybe_unserialize( $requests );
		}
		
		return $requests;
	}
	
	/**
	 * Generate an identifier for $url, which is the same for each variant of its query string, because these variants
	 * share the same local copy.
	 *
	 * @see   Helper::get_local_path()
	 *
	 * @since v1.3.2
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function get_request_id( $url ) {
		$url   = html_entity_decode( trim( $url ) );
		$parts = wp_parse_url( $url );
		
		// The contents of Google Fonts stylesheets are defined by the query string.
		if ( empty( $parts['host'] ) || empty( $parts['path'] ) || self::is_google_fonts_request( $url ) ) {
			return $url;
		}
		
		$port = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		
		return strtolower( $parts['host'] ) . $port . $parts['path'];
	}
	
	/**
	 * Keep the first of each request in $requests.
	 *
	 * @since v1.3.2
	 *
	 * @param array $requests { int => { 'name' => string, 'href' => string } }
	 *
	 * @return array
	 */
	public static function unique_requests( $requests ) {
		$unique = [];
		
		foreach ( $requests as $request ) {
			$id = self::get_request_id( $request['href'] );
			
			if ( ! isset( $unique[ $id ] ) ) {
				$unique[ $id ] = $request;
			}
		}
		
		return array_values( $unique );
	}
	
	/**
	 * Count the requests of $type a local copy is used for.
	 *
	 * @since v1.3.2
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public static function count_local( $type ) {
		$count = 0;
		
		foreach ( self::requests()[ $type ] ?? [] as $request ) {
			if ( self::is_excluded( $type, $request['href'] ) ) {
				continue;
			}
			
			$path = self::is_google_fonts_request( $request['href'] ) ? self::get_local_path_google_font( $request['name'] ) : self::get_local_path( $request['href'], $type );
			
			if ( $path && is_file( $path ) ) {
				$count ++;
			}
		}
		
		return $count;
	}
	
	/**
	 * Check if $url is a file hosted on this site, regardless of its scheme (http, https or protocol relative) and
	 * www-prefix.
	 *
	 * @since v1.3.2
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is_local_request( $url ) {
		$host = wp_parse_url( trim( $url ), PHP_URL_HOST );
		
		// Relative URL.
		if ( null === $host ) {
			return true;
		}
		
		if ( ! $host ) {
			return false;
		}
		
		return in_array( self::normalize_host( $host ), self::local_hosts(), true );
	}
	
	/**
	 * Contains the hosts this site is served from.
	 *
	 * @since v1.3.2
	 *
	 * @return array
	 */
	private static function local_hosts() {
		$hosts = [
			wp_parse_url( get_home_url(), PHP_URL_HOST ),
			wp_parse_url( get_site_url(), PHP_URL_HOST ),
		];
		
		return array_map( [ self::class, 'normalize_host' ], array_filter( $hosts ) );
	}
	
	/**
	 * @since v1.3.2
	 *
	 * @param string $host
	 *
	 * @return string
	 */
	private static function normalize_host( $host ) {
		return preg_replace( '/^www\./', '', strtolower( $host ) );
	}
	
	/**
	 * Contains a list of URL patterns that should be marked as upsells.
	 *
	 * @since v1.4.0
	 *
	 * @return array
	 */
	public static function upsell_list() {
		return apply_filters(
			'gdpress_upsell_list',
			[
				'fonts.googleapis.com/icon',
				'fonts.gstatic.com',
			]
		);
	}
}
